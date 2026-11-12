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
                    $query->select('users.id', 'users.name', 'users.username')
                        ->with([
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

                $checks = collect([1, 2, 3])->map(function ($number) use ($attendances) {
                    $attendance = $attendances
                        ->firstWhere('check_number', $number);

                    return [
                        'check_number' => $number,
                        'is_present' => $attendance?->status === 'present',
                        'is_recorded' => $attendance !== null,
                    ];
                });

                return [
                    'student_id' => $student->id,
                    'name' => $student->name,
                    'username' => $student->username,
                    'checks' => $checks->values(),
                    'final_status' => $this->calculateFinalStatus($attendances),
                ];
            })
            ->values();
    }

    private function calculateFinalStatus(Collection $attendances): string
    {
        $checks = $attendances
            ->whereIn('check_number', [1, 2, 3]);

        if ($checks->contains('status', 'present')) {
            return 'present';
        }

        if ($checks->count() < 3) {
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
            $activeStudentIds = $session->group
                ->enrollments()
                ->where('status', 'active')
                ->pluck('student_id')
                ->map(fn($id) => (int) $id)
                ->all();

            foreach ($students as $studentData) {
                if (
                    !isset($studentData['student_id']) ||
                    !array_key_exists('is_present', $studentData)
                ) {
                    throw new \InvalidArgumentException(
                        'Each student must have a student ID and attendance status.'
                    );
                }

                $studentId = (int) $studentData['student_id'];

                if (!in_array($studentId, $activeStudentIds, true)) {
                    throw new \InvalidArgumentException(
                        "Student {$studentId} is not actively enrolled in this group."
                    );
                }

                $session->attendances()->updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'check_number' => $checkNumber,
                    ],
                    [
                        'status' => $studentData['is_present']
                            ? 'present'
                            : 'absent',
                        'marked_at' => Carbon::now(),
                        'marked_by' => $markedBy->id,
                    ]
                );
            }
        });
    }

}
