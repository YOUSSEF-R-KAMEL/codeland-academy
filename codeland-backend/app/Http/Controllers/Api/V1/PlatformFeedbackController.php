<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePlatformFeedbackRequest;
use App\Models\PlatformFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformFeedbackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PlatformFeedback::query()
            ->with([
                'student:id,name,username',
                'reviewer:id,name,username',
            ]);

        if ($request->user()->hasRole('student')) {
            $query->where('student_id', $request->user()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $feedback = $query
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Platform feedback retrieved successfully.',
            'data' => $feedback,
        ]);
    }

    public function store(
        StorePlatformFeedbackRequest $request
    ): JsonResponse {
        $feedback = PlatformFeedback::create([
            ...$request->validated(),
            'student_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        $feedback->load('student:id,name,username');

        return response()->json([
            'success' => true,
            'message' => 'Feedback submitted successfully.',
            'data' => $feedback,
        ], 201);
    }

    public function update(Request $request, PlatformFeedback $feedback): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                'in:pending,in_progress,resolved,rejected',
            ],

            'admin_reply' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $feedback->update([
            ...$validated,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $feedback->load([
            'student:id,name,username',
            'reviewer:id,name,username',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback updated successfully.',
            'data' => $feedback,
        ]);
    }
}
