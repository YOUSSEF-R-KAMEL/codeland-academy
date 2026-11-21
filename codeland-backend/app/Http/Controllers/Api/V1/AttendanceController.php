<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreAttendanceRequest;
use App\Models\ClassSession;
use App\Models\Group;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends ApiController
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {
    }

    public function index(
        Group $group,
        ClassSession $classSession
    ): JsonResponse {
        $this->authorizeSessionAccess($group, $classSession, request());

        $roster = $this->attendanceService->getSessionRoster($classSession);

        return $this->success(
            'Attendance roster retrieved successfully.',
            [
                'session_id' => $classSession->id,
                'session_title' => $classSession->title,
                'students' => $roster,
            ]
        );
    }

    public function store(
        StoreAttendanceRequest $request,
        Group $group,
        ClassSession $classSession
    ): JsonResponse {
        $this->authorizeSessionAccess($group, $classSession, $request);

        $data = $request->validated();

        try {
            $this->attendanceService->recordAttendance(
                $classSession,
                $data['students'],
                $data['check_number'],
                $request->user()
            );
        } catch (\InvalidArgumentException $exception) {
            return $this->error(
                $exception->getMessage(),
                [],
                422
            );
        }

        return $this->success(
            'Attendance recorded successfully.',
            [
                'session_id' => $classSession->id,
                'check_number' => $data['check_number'],
            ]
        );
    }

    private function authorizeSessionAccess(
        Group $group,
        ClassSession $classSession,
        Request $request
    ): void {
        $user = $request->user();

        abort_unless($user, 403, 'Unauthenticated.');

        abort_unless(
            $classSession->group_id === $group->id,
            404,
            'Class session not found in this group.'
        );

        if ($user->hasRole('admin')) {
            return;
        }

        abort_unless(
            $user->hasRole('instructor'),
            403,
            'You are not authorized to access attendance.'
        );

        abort_unless(
            $group->instructors()
                ->where('users.id', $user->id)
                ->exists(),
            403,
            'You are not assigned to this group.'
        );
    }



    public function groupOverview(Group $group): JsonResponse
    {
        $activeStudentIds = $group->enrollments()
            ->where('status', 'active')
            ->pluck('student_id');

        $totalStudents = $activeStudentIds->count();

        $sessions = $group->sessions()
            ->with('attendances')
            ->orderBy('starts_at')
            ->get();

        $data = $sessions->map(function (ClassSession $session) use ($activeStudentIds, $totalStudents) {
            $present = 0;
            $absent = 0;
            $pending = 0;

            $attendancesByStudent = $session->attendances
                ->whereIn('student_id', $activeStudentIds)
                ->groupBy('student_id');

            foreach ($activeStudentIds as $studentId) {
                $studentAttendances = $attendancesByStudent
                    ->get($studentId, collect())
                    ->whereIn('check_number', [1, 2, 3]);

                if ($studentAttendances->contains('is_present', true)) {
                    $present++;
                    continue;
                }

                $recordedChecks = $studentAttendances
                    ->pluck('check_number')
                    ->unique()
                    ->count();

                if ($recordedChecks === 3) {
                    $absent++;
                } else {
                    $pending++;
                }
            }

            return [
                'session_id' => $session->id,
                'session_title' => $session->title,
                'starts_at' => $session->starts_at,
                'ends_at' => $session->ends_at,
                'session_status' => $session->status,
                'total_students' => $totalStudents,
                'present' => $present,
                'absent' => $absent,
                'pending' => $pending,
                'attendance_percentage' => $totalStudents > 0
                    ? round(($present / $totalStudents) * 100, 2)
                    : 0,
            ];
        });

        return $this->success(
            'Group attendance overview retrieved successfully.',
            [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'sessions' => $data,
            ]
        );
    }



    public function myAttendance(Request $request): JsonResponse
    {
        $student = $request->user();

        abort_unless(
            $student && $student->hasRole('student'),
            403,
            'Only students can access their attendance.'
        );

        $sessions = ClassSession::query()
            ->whereHas('group.enrollments', function ($query) use ($student) {
                $query->where('student_id', $student->id)
                    ->where('status', 'active');
            })
            ->with([
                'group:id,name',
                'courseLesson',
                'attendances' => function ($query) use ($student) {
                    $query->where('student_id', $student->id)
                        ->orderBy('check_number');
                },
            ])
            ->orderByDesc('starts_at')
            ->get();

        $data = $sessions->map(function (ClassSession $session) {
            $attendances = $session->attendances;

            $checks = collect([1, 2, 3])->map(
                function ($number) use ($attendances) {
                    $attendance = $attendances->firstWhere(
                        'check_number',
                        $number
                    );

                    return [
                        'check_number' => $number,
                        'is_present' => $attendance?->is_present,
                        'is_recorded' => $attendance !== null,
                        'marked_at' => $attendance?->marked_at,
                        'notes' => $attendance?->notes,
                    ];
                }
            );

            if ($attendances->contains('is_present', true)) {
                $finalStatus = 'present';
            } elseif (
                $checks->every(
                    fn($check) => $check['is_recorded']
                )
            ) {
                $finalStatus = 'absent';
            } else {
                $finalStatus = 'pending';
            }

            return [
                'session_id' => $session->id,
                'session_title' => $session->title,
                'course_lesson' => $session->courseLesson?->title,
                'group' => [
                    'id' => $session->group->id,
                    'name' => $session->group->name,
                ],
                'starts_at' => $session->starts_at,
                'ends_at' => $session->ends_at,
                'session_status' => $session->status,
                'checks' => $checks->values(),
                'final_status' => $finalStatus,
            ];
        });

        return $this->success(
            'Student attendance retrieved successfully.',
            [
                'student_id' => $student->id,
                'sessions' => $data,
            ]
        );
    }
}
