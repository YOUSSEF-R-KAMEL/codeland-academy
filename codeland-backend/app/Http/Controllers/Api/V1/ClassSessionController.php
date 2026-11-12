<?php

namespace App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClassSessionRequest;
use App\Http\Requests\Api\V1\UpdateClassSessionRequest;
use App\Models\ClassSession;
use App\Models\CourseLesson;
use App\Models\Group;
use Illuminate\Http\JsonResponse;

class ClassSessionController extends Controller
{
    public function index(Group $group): JsonResponse
    {
        $this->authorizeGroupAccess($group);

        $sessions = $group->sessions()
            ->with('courseLesson')
            ->orderBy('starts_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    public function show(
        Group $group,
        ClassSession $classSession
    ): JsonResponse {
        abort_unless($classSession->group_id === $group->id, 404);

        $this->authorizeGroupAccess($group);

        return response()->json([
            'success' => true,
            'data' => $classSession->load('courseLesson', 'attendances'),
        ]);
    }

    public function store(
        StoreClassSessionRequest $request,
        Group $group
    ): JsonResponse {
        $this->authorizeGroupAccess($group);

        $data = $request->validated();

        return DB::transaction(function () use ($group, $data) {
            Group::whereKey($group->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (isset($data['course_lesson_id'])) {
                $lesson = CourseLesson::findOrFail($data['course_lesson_id']);

                abort_unless(
                    $lesson->course_level_id === $group->course_level_id,
                    422,
                    'The selected lesson does not belong to this group course level.'
                );
            }

            $hasConflict = ClassSession::where('group_id', $group->id)
                ->where('status', 'scheduled')
                ->where('starts_at', '<', $data['ends_at'])
                ->where('ends_at', '>', $data['starts_at'])
                ->exists();

            if ($hasConflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'The group already has a scheduled session during this time.',
                ], 422);
            }

            $session = $group->sessions()->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Class session created successfully.',
                'data' => $session->load('courseLesson'),
            ], 201);
        });
    }

    public function update(
        UpdateClassSessionRequest $request,
        Group $group,
        ClassSession $classSession
    ): JsonResponse {
        abort_unless($classSession->group_id === $group->id, 404);

        $this->authorizeGroupAccess($group);

        $data = $request->validated();

        return DB::transaction(function () use ($group, $classSession, $data) {
            Group::whereKey($group->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (isset($data['course_lesson_id'])) {
                $lesson = CourseLesson::findOrFail($data['course_lesson_id']);

                abort_unless(
                    $lesson->course_level_id === $group->course_level_id,
                    422,
                    'The selected lesson does not belong to this group course level.'
                );
            }

            $startsAt = $data['starts_at'] ?? $classSession->starts_at;
            $endsAt = $data['ends_at'] ?? $classSession->ends_at;
            $status = $data['status'] ?? $classSession->status;

            if ($status === 'scheduled') {
                $hasConflict = ClassSession::where('group_id', $group->id)
                    ->where('status', 'scheduled')
                    ->where('id', '!=', $classSession->id)
                    ->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $startsAt)
                    ->exists();

                if ($hasConflict) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The group already has a scheduled session during this time.',
                    ], 422);
                }
            }

            $classSession->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Class session updated successfully.',
                'data' => $classSession->fresh()->load('courseLesson'),
            ]);
        });
    }

    public function destroy(
        Group $group,
        ClassSession $classSession
    ): JsonResponse {
        abort_unless($classSession->group_id === $group->id, 404);

        $this->authorizeGroupAccess($group);

        if ($classSession->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Completed sessions cannot be deleted.',
            ], 422);
        }

        $classSession->delete();

        return response()->json([
            'success' => true,
            'message' => 'Class session deleted successfully.',
        ]);
    }

    private function authorizeGroupAccess(Group $group): void
    {
        $user = request()->user();

        if ($user->hasRole('admin')) {
            return;
        }

        abort_unless(
            $user->hasRole('instructor') &&
            $group->instructors()
                ->where('users.id', $user->id)
                ->exists(),
            403,
            'You are not assigned to this group.'
        );
    }
}
