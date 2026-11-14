<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->user();

        abort_unless($student->hasRole('student'), 403);

        $courseIds = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->with('group:id,course_id')
            ->get()
            ->pluck('group.course_id')
            ->filter()
            ->unique()
            ->values();

        $assignments = Assignment::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('courseLesson', function ($query) use ($courseIds) {
                $query->whereIn('course_id', $courseIds);
            })
            ->with([
                'courseLesson:id,course_id,title,lesson_number',
                'courseLesson.course:id,title',
                'submissions' => function ($query) use ($student) {
                    $query->where('student_id', $student->id)
                        ->with([
                            'feedbackTemplate:id,rating,comment',
                            'reviewer:id,name',
                        ]);
                },
            ])
            ->orderBy('due_at')
            ->paginate(15);

        return response()->json([
            'data' => $assignments,
        ]);
    }

    public function show(
        Request $request,
        Assignment $assignment
    ): JsonResponse {
        $student = $request->user();

        abort_unless($student->hasRole('student'), 403);

        abort_unless(
            $assignment->is_published
            && $assignment->published_at
            && $assignment->published_at->lte(now()),
            404
        );

        $hasEnrollment = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->whereHas('group', function ($query) use ($assignment) {
                $query->where(
                    'course_id',
                    $assignment->courseLesson->course_id
                );
            })
            ->exists();

        abort_unless(
            $hasEnrollment,
            403,
            'You are not enrolled in this course.'
        );

        $assignment->load([
            'courseLesson.course',
            'submissions' => function ($query) use ($student) {
                $query->where('student_id', $student->id)
                    ->with([
                        'feedbackTemplate:id,rating,comment',
                        'reviewer:id,name',
                    ]);
            },
        ]);

        return response()->json([
            'data' => $assignment,
        ]);
    }
}
