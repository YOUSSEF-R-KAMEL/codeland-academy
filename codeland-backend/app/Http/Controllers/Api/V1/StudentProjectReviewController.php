<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StudentProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReviewStudentProjectRequest;
use App\Models\StudentProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentProjectReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole('admin') || $user->hasRole('instructor'),
            403
        );

        $filters = $request->validate([
            'status' => [
                'nullable',
                'string',
                Rule::in(
                    array_column(StudentProjectStatus::cases(), 'value')
                ),
            ],
            'group_id' => [
                'nullable',
                'integer',
                'exists:groups,id',
            ],
            'project_id' => [
                'nullable',
                'integer',
                'exists:projects,id',
            ],
        ]);

        $query = StudentProject::query()
            ->with([
                'student:id,name',
                'project:id,title,course_level_id,group_id',
                'courseLevel',
                'group',
                'reviewer:id,name',
            ]);

        if ($user->hasRole('instructor') && !$user->hasRole('admin')) {
            $groupIds = $user->instructorGroups()
                ->pluck('groups.id');

            $query->whereNotNull('group_id')
                ->whereIn('group_id', $groupIds);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        return response()->json([
            'data' => $query
                ->latest()
                ->paginate(15),
        ]);
    }

    public function review(
        ReviewStudentProjectRequest $request,
        StudentProject $studentProject
    ): JsonResponse {
        $user = $request->user();

        abort_unless(
            $user->hasRole('admin') || $user->hasRole('instructor'),
            403
        );

        $data = $request->validated();

        $nextStatus = StudentProjectStatus::from($data['status']);

        abort_if(
            $nextStatus === StudentProjectStatus::REVIEWED
            && !isset($data['score']),
            422,
            'A score is required when approving a project.'
        );

        $reviewedProject = DB::transaction(function () use ($user, $studentProject, $data, $nextStatus) {
            $submission = StudentProject::query()
                ->lockForUpdate()
                ->findOrFail($studentProject->id);

            if ($user->hasRole('instructor') && !$user->hasRole('admin')) {
                abort_if($submission->group_id === null, 403);

                abort_unless(
                    $user->instructorGroups()
                        ->whereKey($submission->group_id)
                        ->exists(),
                    403
                );
            }

            abort_unless(
                $submission->status->canTransitionTo($nextStatus),
                409,
                'This submission is not available for review.'
            );

            $submission->update([
                'status' => $nextStatus,
                'score' => $data['score'] ?? null,
                'instructor_feedback' => $data['instructor_feedback'] ?? null,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

            return $submission;
        });

        return response()->json([
            'message' => 'Project reviewed successfully.',
            'data' => $reviewedProject->fresh()->load([
                'student:id,name',
                'project:id,title',
                'courseLevel',
                'group',
                'reviewer:id,name',
            ]),
        ]);
    }

    public function startReview(
        Request $request,
        StudentProject $studentProject
    ): JsonResponse {
        $user = $request->user();

        abort_unless(
            $user->hasRole('admin') || $user->hasRole('instructor'),
            403
        );

        $updated = DB::transaction(function () use ($user, $studentProject) {
            $submission = StudentProject::query()
                ->lockForUpdate()
                ->findOrFail($studentProject->id);

            if ($user->hasRole('instructor') && !$user->hasRole('admin')) {
                abort_if($submission->group_id === null, 403);

                abort_unless(
                    $user->instructorGroups()
                        ->whereKey($submission->group_id)
                        ->exists(),
                    403
                );
            }

            abort_unless(
                $submission->status->canTransitionTo(
                    StudentProjectStatus::UNDER_REVIEW
                ),
                409,
                'Only submitted projects can be moved to review.'
            );

            $submission->update([
                'status' => StudentProjectStatus::UNDER_REVIEW,
            ]);

            return $submission;
        });

        return response()->json([
            'message' => 'Project moved to review successfully.',
            'data' => $updated->fresh()->load([
                'student:id,name',
                'project:id,title',
                'courseLevel',
                'group',
            ]),
        ]);
    }
}
