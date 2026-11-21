<?php

namespace App\Services;

use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AttendanceService
{
    public function getStudentAttendanceStatus(
        ClassSession $session,
        User $student
    ): string {
        $attendances = $session->attendances()
            ->where('student_id', $student->id)
            ->get();

        return $this->calculateFinalStatus($attendances);
    }

    public function getSessionRoster(ClassSession $session): Collection
    {
        $enrollments = $session->group
            ->enrollments()
            ->where('status', 'active')
            ->with([
                'student' => function ($query) use ($session) {
                    $query->select(
                        'users.id',
                        'users.name',
                        'users.username'
                    )->with([
                                'attendances' => function ($attendanceQuery) use ($session) {
                                    $attendanceQuery
                                        ->where('class_session_id', $session->id)
                                        ->orderBy('check_number');
                                },
                            ]);
                },
            ])
            ->get();

        return $enrollments
            ->filter(fn($enrollment) => $enrollment->student !== null)
            ->map(function ($enrollment) {
                $student = $enrollment->student;
                $attendances = $student->attendances;

                $checks = collect([1, 2, 3])->map(
                    function ($number) use ($attendances) {
                        $attendance = $attendances
                            ->firstWhere('check_number', $number);

                        return [
                            'check_number' => $number,
                            'is_present' => $attendance?->is_present,
                            'is_recorded' => $attendance !== null,
                        ];
                    }
                );

                return [
                    'student_id' => $student->id,
                    'name' => $student->name,
                    'username' => $student->username,
                    'checks' => $checks->values(),
                    'final_status' => $this->calculateFinalStatus(
                        $attendances
                    ),
                ];
            })
            ->values();
    }

    private function calculateFinalStatus(Collection $attendances): string
    {
        $checks = $attendances->whereIn('check_number', [1, 2, 3]);

        if ($checks->contains('is_present', true)) {
            return 'present';
        }

        $recordedChecks = $checks
            ->pluck('check_number')
            ->unique();

        if ($recordedChecks->count() < 3) {
            return 'pending';
        }

        return 'absent';
    }

    public function recordAttendance(
        ClassSession $session,
        array $students,
        int $checkNumber,
        User $markedBy
    ): void {
        if ($checkNumber < 1 || $checkNumber > 3) {
            throw new \InvalidArgumentException(
                'Check number must be between 1 and 3.'
            );
        }

        DB::transaction(function () use ($session, $students, $checkNumber, $markedBy) {
            $lockedSession = ClassSession::whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSession->status !== 'scheduled') {
                throw new \InvalidArgumentException(
                    'Attendance can only be recorded for scheduled sessions.'
                );
            }

            $activeStudentIds = $lockedSession->group
                ->enrollments()
                ->where('status', 'active')
                ->pluck('student_id')
                ->map(fn($id) => (int) $id)
                ->all();

            $submittedStudentIds = [];

            foreach ($students as $studentData) {
                if (
                    !isset($studentData['student_id']) ||
                    !array_key_exists('is_present', $studentData)
                ) {
                    throw new \InvalidArgumentException(
                        'Each student must have a student ID and attendance status.'
                    );
                }

                $submittedStudentIds[] = (int) $studentData['student_id'];
            }

            if (
                count($submittedStudentIds) !==
                count(array_unique($submittedStudentIds))
            ) {
                throw new \InvalidArgumentException(
                    'Duplicate students are not allowed.'
                );
            }

            sort($activeStudentIds);
            sort($submittedStudentIds);

            if ($activeStudentIds !== $submittedStudentIds) {
                throw new \InvalidArgumentException(
                    'Attendance must be submitted for all actively enrolled students.'
                );
            }

            foreach ($students as $studentData) {
                if (
                    !in_array(
                        $studentData['is_present'],
                        [true, false, 0, 1, '0', '1'],
                        true
                    )
                ) {
                    throw new \InvalidArgumentException(
                        'Attendance status must be a valid boolean value.'
                    );
                }

                $studentId = (int) $studentData['student_id'];

                $lockedSession->attendances()->updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'check_number' => $checkNumber,
                    ],
                    [
                        'is_present' => (bool) $studentData['is_present'],
                        'marked_at' => Carbon::now(),
                        'marked_by' => $markedBy->id,
                    ]
                );
            }
        });
    }
}
