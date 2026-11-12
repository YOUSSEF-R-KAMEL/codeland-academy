<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreAttendanceRequest;
use App\Models\ClassSession;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends ApiController
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {
    }

    public function index(ClassSession $classSession): JsonResponse
    {
        $this->authorizeSessionAccess($classSession, request());

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
        ClassSession $classSession
    ): JsonResponse {
        $this->authorizeSessionAccess($classSession, $request);

        try {
            $this->attendanceService->recordAttendance(
                $classSession,
                $request->validated()['students'],
                $request->validated()['check_number'],
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
                'check_number' => $request->validated()['check_number'],
            ]
        );
    }

    private function authorizeSessionAccess(
        ClassSession $classSession,
        Request $request
    ): void {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return;
        }

        abort_unless(
            $classSession->group
                ->instructors()
                ->where('users.id', $user->id)
                ->exists(),
            403,
            'You are not assigned to this group.'
        );
    }
}
