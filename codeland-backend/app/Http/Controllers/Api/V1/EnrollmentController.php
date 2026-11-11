<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEnrollmentRequest;
use App\Http\Requests\Api\V1\TransferEnrollmentRequest;
use App\Http\Requests\Api\V1\UpdateEnrollmentRequest;
use App\Models\Enrollment;
use App\Models\Group;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    public function index(Group $group): JsonResponse
    {
        $enrollments = $group->enrollments()
            ->with([
                'student.roles',
                'invoices.installments',
            ])
            ->orderByDesc('enrolled_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $enrollments,
        ]);
    }

    public function show(
        Group $group,
        Enrollment $enrollment
    ): JsonResponse {
        abort_unless($enrollment->group_id === $group->id, 404);

        $enrollment->load([
            'student.roles',
            'invoices.installments',
        ]);

        return response()->json([
            'success' => true,
            'data' => $enrollment,
        ]);
    }

    public function store(
        StoreEnrollmentRequest $request,
        Group $group,
        InvoiceService $invoiceService
    ): JsonResponse {
        $data = $request->validated();

        $student = User::findOrFail($data['student_id']);

        if (!$student->hasRole('student')) {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not a student.',
            ], 422);
        }

        $enrollment = DB::transaction(function () use ($group, $student, $data, $invoiceService) {
            $lockedGroup = Group::whereKey($group->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existing = Enrollment::withTrashed()
                ->where('group_id', $lockedGroup->id)
                ->where('student_id', $student->id)
                ->first();

            if ($existing && !$existing->trashed()) {
                if ($existing->status !== 'cancelled') {
                    return null;
                }
            }

            $occupiedSeats = $lockedGroup->enrollments()
                ->whereIn('status', ['pending', 'active'])
                ->count();

            if ($occupiedSeats >= $lockedGroup->capacity) {
                return false;
            }

            $enrollmentData = [
                'enrolled_at' => $data['enrolled_at'] ?? now()->toDateString(),
                'status' => $data['status'] ?? 'pending',
                'notes' => $data['notes'] ?? null,
            ];

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }

                $existing->update($enrollmentData);

                $enrollment = $existing;
            } else {
                $enrollment = $lockedGroup->enrollments()->create([
                    'student_id' => $student->id,
                    ...$enrollmentData,
                ]);
            }

            $invoiceService->createForEnrollment(
                $enrollment,
                $data['payment_plan']
            );

            return $enrollment;
        });

        if ($enrollment === null) {
            return response()->json([
                'success' => false,
                'message' => 'The student already has an enrollment in this group.',
            ], 422);
        }

        if ($enrollment === false) {
            return response()->json([
                'success' => false,
                'message' => 'The group has reached its maximum capacity.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Student enrolled successfully.',
            'data' => $enrollment->load([
                'student.roles',
                'invoices.installments',
            ]),
        ], 201);
    }

    public function update(
        UpdateEnrollmentRequest $request,
        Group $group,
        Enrollment $enrollment
    ): JsonResponse {
        abort_unless($enrollment->group_id === $group->id, 404);

        $data = $request->validated();

        $updated = DB::transaction(function () use ($group, $enrollment, $data) {
            $lockedGroup = Group::whereKey($group->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                isset($data['status']) &&
                in_array($data['status'], ['pending', 'active'], true) &&
                !in_array($enrollment->status, ['pending', 'active'], true)
            ) {
                $occupiedSeats = $lockedGroup->enrollments()
                    ->whereIn('status', ['pending', 'active'])
                    ->where('id', '!=', $enrollment->id)
                    ->count();

                if ($occupiedSeats >= $lockedGroup->capacity) {
                    return false;
                }
            }

            $enrollment->update($data);

            return true;
        });

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'The group has reached its maximum capacity.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Enrollment updated successfully.',
            'data' => $enrollment->fresh()->load([
                'student.roles',
                'invoices.installments',
            ]),
        ]);
    }

    public function destroy(
        Group $group,
        Enrollment $enrollment
    ): JsonResponse {
        abort_unless($enrollment->group_id === $group->id, 404);

        $enrollment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Enrollment cancelled successfully.',
        ]);
    }

    public function transfer(
        TransferEnrollmentRequest $request,
        Group $group,
        Enrollment $enrollment
    ): JsonResponse {
        abort_unless($enrollment->group_id === $group->id, 404);

        if (!in_array($enrollment->status, ['pending', 'active'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending or active enrollments can be transferred.',
            ], 422);
        }

        $targetGroupId = $request->validated('target_group_id');

        if ((int) $targetGroupId === (int) $group->id) {
            return response()->json([
                'success' => false,
                'message' => 'The target group must be different.',
            ], 422);
        }

        $result = DB::transaction(function () use ($group, $enrollment, $targetGroupId) {
            $groups = Group::whereIn('id', [$group->id, $targetGroupId])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $sourceGroup = $groups->get($group->id);
            $targetGroup = $groups->get($targetGroupId);

            if (!$targetGroup) {
                return ['error' => 'target_not_found'];
            }

            if ($sourceGroup->course_level_id !== $targetGroup->course_level_id) {
                return ['error' => 'different_course_level'];
            }

            $currentEnrollment = Enrollment::whereKey($enrollment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($currentEnrollment->status, ['pending', 'active'], true)) {
                return ['error' => 'invalid_status'];
            }

            $existing = Enrollment::withTrashed()
                ->where('group_id', $targetGroup->id)
                ->where('student_id', $currentEnrollment->student_id)
                ->first();

            if (
                $existing &&
                !$existing->trashed() &&
                $existing->status !== 'cancelled'
            ) {
                return ['error' => 'already_enrolled'];
            }

            $occupiedSeats = $targetGroup->enrollments()
                ->whereIn('status', ['pending', 'active'])
                ->count();

            if ($occupiedSeats >= $targetGroup->capacity) {
                return ['error' => 'group_full'];
            }

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }

                $existing->update([
                    'enrolled_at' => now()->toDateString(),
                    'status' => 'active',
                    'notes' => $currentEnrollment->notes,
                ]);

                $newEnrollment = $existing;
            } else {
                $newEnrollment = $targetGroup->enrollments()->create([
                    'student_id' => $currentEnrollment->student_id,
                    'enrolled_at' => now()->toDateString(),
                    'status' => 'active',
                    'notes' => $currentEnrollment->notes,
                ]);
            }

            Invoice::where('enrollment_id', $currentEnrollment->id)
                ->update([
                    'enrollment_id' => $newEnrollment->id,
                ]);

            $currentEnrollment->update([
                'status' => 'cancelled',
            ]);

            return ['enrollment' => $newEnrollment];
        });

        if (isset($result['error'])) {
            $messages = [
                'target_not_found' => ['Target group not found.', 404],
                'different_course_level' => [
                    'Students can only be transferred between groups of the same course level.',
                    422,
                ],
                'invalid_status' => [
                    'Only pending or active enrollments can be transferred.',
                    422,
                ],
                'already_enrolled' => [
                    'The student is already enrolled in the target group.',
                    422,
                ],
                'group_full' => [
                    'The target group has reached its maximum capacity.',
                    422,
                ],
            ];

            [$message, $status] = $messages[$result['error']];

            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return response()->json([
            'success' => true,
            'message' => 'Student transferred successfully.',
            'data' => $result['enrollment']->load([
                'student.roles',
                'invoices.installments',
            ]),
        ]);
    }
}
