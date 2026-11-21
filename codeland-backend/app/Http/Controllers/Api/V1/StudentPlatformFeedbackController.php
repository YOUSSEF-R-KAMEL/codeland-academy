<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StorePlatformFeedbackRequest;
use App\Models\PlatformFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentPlatformFeedbackController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $feedback = PlatformFeedback::query()
            ->where('student_id', Auth::id())
            ->select([
                'id',
                'category',
                'subject',
                'message',
                'status',
                'admin_reply',
                'reviewed_at',
                'created_at',
            ])
            ->latest()
            ->paginate(15);

        return $this->success(
            'Your platform feedback retrieved successfully.',
            ['feedback' => $feedback]
        );
    }

    public function store(StorePlatformFeedbackRequest $request): JsonResponse
    {
        $feedback = PlatformFeedback::create([
            ...$request->validated(),
            'student_id' => Auth::id(),
        ]);

        return $this->success(
            'Your feedback has been submitted successfully.',
            ['feedback' => $feedback],
            201
        );
    }
}
