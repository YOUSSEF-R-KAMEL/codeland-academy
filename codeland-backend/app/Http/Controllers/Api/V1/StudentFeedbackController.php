<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreStudentFeedbackRequest;
use App\Models\StudentFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentFeedbackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $feedback = StudentFeedback::query()
            ->where('student_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $feedback,
        ]);
    }

    public function store(
        StoreStudentFeedbackRequest $request
    ): JsonResponse {
        $feedback = StudentFeedback::create([
            'student_id' => $request->user()->id,
            'type' => $request->validated('type'),
            'subject' => $request->validated('subject'),
            'message' => $request->validated('message'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your feedback has been submitted successfully.',
            'data' => $feedback,
        ], 201);
    }

    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $feedback = StudentFeedback::query()
            ->where('student_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $feedback,
        ]);
    }
}
