<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreCourseEvaluationRequest;
use App\Models\CourseEvaluation;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class StudentCourseEvaluationController extends ApiController
{
    public function store(StoreCourseEvaluationRequest $request): JsonResponse
    {
        $studentId = Auth::id();
        $courseId = $request->validated('course_id');

        $existingEvaluation = CourseEvaluation::query()
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->exists();

        if ($existingEvaluation) {
            return $this->error(
                'You have already evaluated this course.',
                [],
                409
            );
        }

        $completedGroup = Group::query()
            ->where('course_id', $courseId)
            ->whereHas('enrollments', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->whereHas('sessions')
            ->whereDoesntHave('sessions', function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->with([
                'instructors' => function ($query) {
                    $query->wherePivot('is_primary', true);
                },
            ])
            ->orderByDesc('end_date')
            ->first();

        if (!$completedGroup) {
            return $this->error(
                'You can only evaluate a course after completing all its sessions.',
                [],
                403
            );
        }

        $instructor = $completedGroup->instructors->first();

        if (!$instructor) {
            return $this->error(
                'No primary instructor is assigned to this group.',
                [],
                422
            );
        }

        $evaluation = CourseEvaluation::create([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'instructor_id' => $instructor->id,
            'course_rating' => $request->validated('course_rating'),
            'course_comment' => $request->validated('course_comment'),
            'instructor_rating' => $request->validated('instructor_rating'),
            'instructor_comment' => $request->validated('instructor_comment'),
        ]);

        $evaluation->load([
            'course:id,title',
            'instructor:id,name',
        ]);

        return $this->success(
            'Course evaluation submitted successfully.',
            ['evaluation' => $evaluation],
            201
        );
    }
}
