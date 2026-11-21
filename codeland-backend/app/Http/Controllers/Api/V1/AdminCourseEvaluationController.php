<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\FilterEvaluationsRequest;
use App\Models\CourseEvaluation;
use Illuminate\Http\JsonResponse;

class AdminCourseEvaluationController extends ApiController
{
    public function index(FilterEvaluationsRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $query = CourseEvaluation::query()
            ->with([
                'student:id,name',
                'course:id,title',
                'instructor:id,name',
            ]);

        if (!empty($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        if (!empty($filters['instructor_id'])) {
            $query->where('instructor_id', $filters['instructor_id']);
        }

        if (!empty($filters['rating'])) {
            $rating = $filters['rating'];

            $query->where(function ($subQuery) use ($rating) {
                $subQuery->where('course_rating', $rating)
                    ->orWhere('instructor_rating', $rating);
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('course_comment', 'like', "%{$search}%")
                    ->orWhere('instructor_comment', 'like', "%{$search}%");
            });
        }

        $evaluations = $query
            ->latest()
            ->paginate($filters['per_page'] ?? 15);

        return $this->success(
            'Course evaluations retrieved successfully.',
            ['evaluations' => $evaluations]
        );
    }

    public function statistics(): JsonResponse
    {
        $query = CourseEvaluation::query();

        $statistics = [
            'total_evaluations' => (clone $query)->count(),

            'average_course_rating' => round(
                (float) (clone $query)->avg('course_rating'),
                2
            ),

            'average_instructor_rating' => round(
                (float) (clone $query)->avg('instructor_rating'),
                2
            ),

            'course_rating_distribution' => (clone $query)
                ->selectRaw('course_rating as rating, COUNT(*) as total')
                ->groupBy('course_rating')
                ->orderBy('course_rating')
                ->get(),

            'instructor_rating_distribution' => (clone $query)
                ->selectRaw('instructor_rating as rating, COUNT(*) as total')
                ->groupBy('instructor_rating')
                ->orderBy('instructor_rating')
                ->get(),
        ];

        return $this->success(
            'Evaluation statistics retrieved successfully.',
            ['statistics' => $statistics]
        );
    }

    public function instructorFeedback(
        FilterEvaluationsRequest $request
    ): JsonResponse {
        $filters = $request->validated();

        $query = CourseEvaluation::query()
            ->with([
                'student:id,name',
                'course:id,title',
                'instructor:id,name',
            ])
            ->where(function ($subQuery) {
                $subQuery->whereNotNull('instructor_comment')
                    ->orWhere('instructor_rating', '<', 5);
            });

        if (!empty($filters['instructor_id'])) {
            $query->where('instructor_id', $filters['instructor_id']);
        }

        if (!empty($filters['rating'])) {
            $query->where('instructor_rating', $filters['rating']);
        }

        $feedback = $query
            ->latest()
            ->paginate($filters['per_page'] ?? 15);

        return $this->success(
            'Instructor feedback retrieved successfully.',
            ['feedback' => $feedback]
        );
    }
}
