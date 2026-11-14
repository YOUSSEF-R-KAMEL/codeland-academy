<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReviewAssignmentSubmissionRequest;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentSubmissionReviewController extends Controller
{
    public function index(
        Request $request,
        Assignment $assignment
    ): JsonResponse {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            $submissions = AssignmentSubmission::query()
                ->where('assignment_id', $assignment->id)
                ->with([
                    'student:id,name,username',
                    'reviewer:id,name',
                    'feedbackTemplate:id,rating,comment',
                ])
                ->latest('submitted_at')
                ->paginate(15);
        } else {
            $courseId = $assignment->courseLesson->course_id;

            $authorizedStudentIds = Enrollment::query()
                ->where('status', 'active')
                ->whereHas('group', function ($query) use ($courseId, $user) {
                    $query->where('course_id', $courseId)
                        ->whereHas('instructors', function ($instructorQuery) use ($user) {
                            $instructorQuery->where('users.id', $user->id);
                        });
                })
                ->pluck('student_id');

            $submissions = AssignmentSubmission::query()
                ->where('assignment_id', $assignment->id)
                ->whereIn('student_id', $authorizedStudentIds)
                ->with([
                    'student:id,name,username',
                    'reviewer:id,name',
                    'feedbackTemplate:id,rating,comment',
                ])
                ->latest('submitted_at')
                ->paginate(15);
        }

        return response()->json([
            'data' => $submissions,
        ]);
    }

    public function review(
        ReviewAssignmentSubmissionRequest $request,
        AssignmentSubmission $submission
    ): JsonResponse {
        $user = $request->user();

        abort_unless(
            in_array($submission->status, ['submitted', 'reviewed'], true),
            422,
            'This submission cannot be reviewed.'
        );

        abort_unless(
            $submission->submitted_at !== null,
            422,
            'This submission has not been submitted yet.'
        );

        if (!$user->hasRole('admin')) {
            $courseId = $submission->assignment->courseLesson->course_id;

            $hasAccess = Enrollment::query()
                ->where('student_id', $submission->student_id)
                ->where('status', 'active')
                ->whereHas('group', function ($query) use ($courseId, $user) {
                    $query->where('course_id', $courseId)
                        ->whereHas('instructors', function ($instructorQuery) use ($user) {
                            $instructorQuery->where('users.id', $user->id);
                        });
                })
                ->exists();

            abort_unless(
                $hasAccess,
                403,
                'You are not authorized to review this submission.'
            );
        }

        $submission->update([
            'rating' => $request->validated('rating'),
            'feedback_template_id' => $request->validated('feedback_template_id'),
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'status' => 'reviewed',
            'score' => null,
            'instructor_feedback' => null,
        ]);

        $submission->load([
            'student:id,name,username',
            'reviewer:id,name',
            'feedbackTemplate:id,rating,comment',
        ]);

        return response()->json([
            'message' => 'Assignment submission reviewed successfully.',
            'data' => $submission,
        ]);
    }
}
