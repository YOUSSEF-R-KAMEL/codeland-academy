<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreGroupRequest;
use App\Http\Requests\Api\V1\UpdateGroupRequest;
use App\Models\Course;
use App\Models\Group;
use Illuminate\Http\JsonResponse;

class GroupController extends Controller
{
    public function index(Course $course): JsonResponse
    {
        $groups = $course->groups()
            ->with([
                'instructors:id,name,email',
            ])
            ->withCount('enrollments')
            ->orderBy('start_date')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }

    public function show(Course $course, Group $group): JsonResponse
    {
        abort_unless($group->course_id === $course->id, 404);

        return response()->json([
            'success' => true,
            'data' => $group,
        ]);
    }

    public function store(
        StoreGroupRequest $request,
        Course $course
    ): JsonResponse {
        $group = $course->groups()->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully.',
            'data' => $group,
        ], 201);
    }

    public function update(
        UpdateGroupRequest $request,
        Course $course,
        Group $group
    ): JsonResponse {
        abort_unless($group->course_id === $course->id, 404);

        $group->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Group updated successfully.',
            'data' => $group->fresh(),
        ]);
    }


    public function destroy(
        Course $course,
        Group $group
    ): JsonResponse {
        abort_unless($group->course_id === $course->id, 404);

        $relatedRecords = [
            'enrollments' => $group->enrollments()->withTrashed()->exists(),
            'sessions' => $group->sessions()->exists(),
            'instructors' => $group->instructors()->exists(),
            'student_projects' => $group->studentProjects()->exists(),
            'announcements' => $group->announcements()->withTrashed()->exists(),
            'student_evaluations' => $group->studentEvaluations()->exists(),
            'student_questions' => $group->studentQuestions()->exists(),
            'projects' => $group->projects()->exists(),
        ];

        $existingRecords = array_keys(
            array_filter($relatedRecords)
        );

        if (!empty($existingRecords)) {
            return response()->json([
                'success' => false,
                'message' => 'This group cannot be deleted because it has related academic records.',
                'related_records' => $existingRecords,
            ], 422);
        }

        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'Group deleted successfully.',
        ]);
    }

}
