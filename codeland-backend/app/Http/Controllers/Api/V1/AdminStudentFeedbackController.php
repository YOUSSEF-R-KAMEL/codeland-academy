<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReplyStudentFeedbackRequest;
use App\Http\Requests\Api\V1\UpdateStudentFeedbackStatusRequest;
use App\Models\StudentFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminStudentFeedbackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:suggestion,complaint,inquiry',
            'status' => 'sometimes|in:new,in_review,resolved,rejected',
            'search' => 'sometimes|string|max:255',
            'per_page' => 'sometimes|integer|min:5|max:100',
        ]);

        $query = StudentFeedback::query()
            ->with([
                'student:id,name,email',
                'admin:id,name',
            ]);

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $feedback = $query
            ->latest()
            ->paginate($validated['per_page'] ?? 10);

        return response()->json([
            'success' => true,
            'data' => $feedback,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $feedback = StudentFeedback::with([
            'student:id,name,email',
            'admin:id,name',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $feedback,
        ]);
    }

    public function reply(
        ReplyStudentFeedbackRequest $request,
        int $id
    ): JsonResponse {
        $feedback = DB::transaction(function () use ($request, $id) {
            $feedback = StudentFeedback::query()
                ->lockForUpdate()
                ->findOrFail($id);

            if ($feedback->admin_reply !== null) {
                return null;
            }

            $feedback->update([
                'admin_reply' => $request->validated('admin_reply'),
                'replied_by' => $request->user()->id,
                'replied_at' => now(),
            ]);

            return $feedback->load([
                'student:id,name,email',
                'admin:id,name',
            ]);
        });

        if ($feedback === null) {
            return response()->json([
                'success' => false,
                'message' => 'This feedback has already been replied to.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reply submitted successfully.',
            'data' => $feedback,
        ]);
    }

    public function updateStatus(
        UpdateStudentFeedbackStatusRequest $request,
        int $id
    ): JsonResponse {
        $feedback = StudentFeedback::findOrFail($id);

        $feedback->update([
            'status' => $request->validated('status'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback status updated successfully.',
            'data' => $feedback,
        ]);
    }
}
