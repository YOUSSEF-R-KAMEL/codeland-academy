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
        $levelIds = Enrollment::query()
            ->where('student_id', $request->user()->id)
            ->where('status', 'active')
            ->with('group:id,course_level_id')
            ->get()
            ->pluck('group.course_level_id')
            ->filter()
            ->unique()
            ->values();

        $assignments = Assignment::query()
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('courseLesson', function ($query) use ($levelIds) {
                $query->whereIn('course_level_id', $levelIds);
            })
            ->with([
                'courseLesson:id,course_level_id,title,lesson_number',
                'courseLesson.courseLevel:id,course_id,name',
                'courseLesson.courseLevel.course:id,title',
            ])
            ->with([
                'submissions' => function ($query) use ($request) {
                    $query->where('student_id', $request->user()->id)
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
        abort_unless(
            $assignment->is_published
            && $assignment->published_at
            && $assignment->published_at->lte(now()),
            404
        );

        $hasEnrollment = Enrollment::query()
            ->where('student_id', $request->user()->id)
            ->where('status', 'active')
            ->whereHas('group', function ($query) use ($assignment) {
                $query->where(
                    'course_level_id',
                    $assignment->courseLesson->course_level_id
                );
            })
            ->exists();

        abort_unless($hasEnrollment, 403, 'You are not enrolled in this lesson level.');

        $assignment->load([
            'courseLesson.courseLevel.course',
            'submissions' => function ($query) use ($request) {
                $query->where('student_id', $request->user()->id)
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
