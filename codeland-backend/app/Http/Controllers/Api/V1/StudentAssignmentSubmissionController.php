<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAssignmentSubmissionRequest;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;

class StudentAssignmentSubmissionController extends Controller
{
    public function store(
        StoreAssignmentSubmissionRequest $request,
        Assignment $assignment
    ): JsonResponse {
        abort_unless(
            $assignment->is_published
            && $assignment->published_at
            && $assignment->published_at->lte(now()),
            404
        );

        $student = $request->user();

        $hasEnrollment = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->whereHas('group', function ($query) use ($assignment) {
                $query->where(
                    'course_level_id',
                    $assignment->courseLesson->course_level_id
                );
            })
            ->exists();

        abort_unless(
            $hasEnrollment,
            403,
            'You are not enrolled in this assignment level.'
        );

        $submission = AssignmentSubmission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
            ],
            [
                'submission_url' => $request->validated('submission_url'),
                'submission_notes' => $request->validated('submission_notes'),
                'submitted_at' => now(),
                'status' => 'submitted',
                'score' => null,
                'instructor_feedback' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );

        return response()->json([
            'message' => 'Assignment submitted successfully.',
            'data' => $submission,
        ], 200);
    }
}
