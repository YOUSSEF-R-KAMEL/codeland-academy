<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCourseEvaluationRequest;
use App\Models\CourseEvaluation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseEvaluationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CourseEvaluation::query()
            ->with([
                'student:id,name,username',
                'course:id,title',
                'instructor:id,name,username',
            ]);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->integer('course_id'));
        }

        if ($request->filled('instructor_id')) {
            $query->where('instructor_id', $request->integer('instructor_id'));
        }

        $evaluations = $query
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Course evaluations retrieved successfully.',
            'data' => $evaluations,
        ]);
    }

    public function store(
        StoreCourseEvaluationRequest $request
    ): JsonResponse {
        $studentId = $request->user()->id;

        $evaluation = DB::transaction(function () use ($request, $studentId) {
            return CourseEvaluation::create([
                ...$request->validated(),
                'student_id' => $studentId,
            ]);
        });

        $evaluation->load([
            'student:id,name,username',
            'course:id,title',
            'instructor:id,name,username',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Course evaluation submitted successfully.',
            'data' => $evaluation,
        ], 201);
    }
}
