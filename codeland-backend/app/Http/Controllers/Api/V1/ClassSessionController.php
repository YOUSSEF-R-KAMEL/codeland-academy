<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClassSessionRequest;
use App\Http\Requests\Api\V1\UpdateClassSessionRequest;
use App\Models\ClassSession;
use App\Models\CourseLesson;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
                    $lesson->course_id === $group->course_id,
                    422,
                    'The selected lesson does not belong to this group course.'
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

            $data['status'] = 'scheduled';

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

        if ($classSession->status !== 'scheduled') {
            return response()->json([
                'success' => false,
                'message' => 'Only scheduled sessions can be updated.',
            ], 422);
        }

        $data = $request->validated();

        return DB::transaction(function () use ($group, $classSession, $data) {
            Group::whereKey($group->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (isset($data['course_lesson_id'])) {
                $lesson = CourseLesson::findOrFail($data['course_lesson_id']);

                abort_unless(
                    $lesson->course_id === $group->course_id,
                    422,
                    'The selected lesson does not belong to this group course.'
                );
            }

            $startsAt = $data['starts_at'] ?? $classSession->starts_at;
            $endsAt = $data['ends_at'] ?? $classSession->ends_at;

            if ($endsAt <= $startsAt) {
                return response()->json([
                    'success' => false,
                    'message' => 'The session end time must be after its start time.',
                ], 422);
            }

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


    public function attendanceSessions(): JsonResponse
    {
        $sessions = ClassSession::query()
            ->with([
                'group.course',
                'group.instructors' => function ($query) {
                    $query->wherePivot('is_primary', true);
                },
            ])
            ->addSelect([
                // Count each student once if they attended any check.
                'present_count' => DB::table('attendances as a')
                    ->selectRaw('COUNT(DISTINCT a.student_id)')
                    ->whereColumn('a.class_session_id', 'class_sessions.id')
                    ->where('a.is_present', true),

                // Count each student once only after all 3 checks are absent.
                'absent_count' => DB::table('attendances as a')
                    ->selectRaw('COUNT(DISTINCT a.student_id)')
                    ->whereColumn('a.class_session_id', 'class_sessions.id')
                    ->whereNotExists(function ($query) {
                        $query->selectRaw('1')
                            ->from('attendances as p')
                            ->whereColumn('p.class_session_id', 'a.class_session_id')
                            ->whereColumn('p.student_id', 'a.student_id')
                            ->where('p.is_present', true);
                    })
                    ->whereExists(function ($query) {
                        $query->selectRaw('1')
                            ->from('attendances as c1')
                            ->whereColumn('c1.class_session_id', 'a.class_session_id')
                            ->whereColumn('c1.student_id', 'a.student_id')
                            ->where('c1.check_number', 1);
                    })
                    ->whereExists(function ($query) {
                        $query->selectRaw('1')
                            ->from('attendances as c2')
                            ->whereColumn('c2.class_session_id', 'a.class_session_id')
                            ->whereColumn('c2.student_id', 'a.student_id')
                            ->where('c2.check_number', 2);
                    })
                    ->whereExists(function ($query) {
                        $query->selectRaw('1')
                            ->from('attendances as c3')
                            ->whereColumn('c3.class_session_id', 'a.class_session_id')
                            ->whereColumn('c3.student_id', 'a.student_id')
                            ->where('c3.check_number', 3);
                    }),
            ])
            ->withCount([
                'group as active_students_count' => function ($query) {
                    $query->join(
                        'enrollments',
                        'enrollments.group_id',
                        '=',
                        'groups.id'
                    )->where('enrollments.status', 'active');
                },
            ])
            ->orderByDesc('starts_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

}
