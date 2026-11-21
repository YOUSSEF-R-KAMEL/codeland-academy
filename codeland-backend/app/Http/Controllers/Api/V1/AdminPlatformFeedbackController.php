<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\UpdatePlatformFeedbackRequest;
use App\Models\PlatformFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPlatformFeedbackController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = PlatformFeedback::query()
            ->with([
                'student:id,name',
                'reviewer:id,name',
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $feedback = $query
            ->latest()
            ->paginate(15);

        return $this->success(
            'Platform feedback retrieved successfully.',
            ['feedback' => $feedback]
        );
    }

    public function update(
        UpdatePlatformFeedbackRequest $request,
        PlatformFeedback $platformFeedback
    ): JsonResponse {
        $platformFeedback->update([
            ...$request->validated(),
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $platformFeedback->load([
            'student:id,name',
            'reviewer:id,name',
        ]);

        return $this->success(
            'Platform feedback updated successfully.',
            ['feedback' => $platformFeedback]
        );
    }
}
