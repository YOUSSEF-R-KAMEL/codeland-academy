<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreFeedbackTemplateRequest;
use App\Http\Requests\Api\V1\UpdateFeedbackTemplateRequest;
use App\Models\FeedbackTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = FeedbackTemplate::query();

        if (!$request->user()->hasRole('admin')) {
            $query->where('is_active', true);
        }

        if ($request->filled('rating')) {
            $request->validate([
                'rating' => ['integer', 'between:1,5'],
            ]);

            $query->where('rating', $request->integer('rating'));
        }

        return response()->json([
            'data' => $query
                ->orderBy('rating')
                ->orderBy('sort_order')
                ->get([
                    'id',
                    'rating',
                    'comment',
                    'is_active',
                    'sort_order',
                ]),
        ]);
    }

    public function store(
        StoreFeedbackTemplateRequest $request
    ): JsonResponse {
        $template = FeedbackTemplate::create(
            $request->validated()
        );

        return response()->json([
            'message' => 'Feedback template created successfully.',
            'data' => $template,
        ], 201);
    }

    public function show(
        FeedbackTemplate $feedbackTemplate
    ): JsonResponse {
        return response()->json([
            'data' => $feedbackTemplate,
        ]);
    }

    public function update(
        UpdateFeedbackTemplateRequest $request,
        FeedbackTemplate $feedbackTemplate
    ): JsonResponse {
        $feedbackTemplate->update(
            $request->validated()
        );

        return response()->json([
            'message' => 'Feedback template updated successfully.',
            'data' => $feedbackTemplate->fresh(),
        ]);
    }

    public function destroy(
        FeedbackTemplate $feedbackTemplate
    ): JsonResponse {
        $feedbackTemplate->update([
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Feedback template deactivated successfully.',
        ]);
    }
}
