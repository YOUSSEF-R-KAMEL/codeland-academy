<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreGroupRequest;
use App\Http\Requests\Api\V1\UpdateGroupRequest;
use App\Models\CourseLevel;
use App\Models\Group;
use Illuminate\Http\JsonResponse;

class GroupController extends Controller
{
    public function index(CourseLevel $level): JsonResponse
    {
        $groups = Group::where('course_level_id', $level->id)
            ->orderBy('start_date')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }

    public function show(CourseLevel $level, Group $group): JsonResponse
    {
        abort_unless($group->course_level_id === $level->id, 404);

        return response()->json([
            'success' => true,
            'data' => $group,
        ]);
    }

    public function store(
        StoreGroupRequest $request,
        CourseLevel $level
    ): JsonResponse {
        $group = new Group();

        $group->course_level_id = $level->id;

        foreach ($request->validated() as $key => $value) {
            $group->{$key} = $value;
        }

        $group->save();

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully.',
            'data' => $group,
        ], 201);
    }

    public function update(
        UpdateGroupRequest $request,
        CourseLevel $level,
        Group $group
    ): JsonResponse {
        abort_unless($group->course_level_id === $level->id, 404);

        foreach ($request->validated() as $key => $value) {
            $group->{$key} = $value;
        }

        $group->save();

        return response()->json([
            'success' => true,
            'message' => 'Group updated successfully.',
            'data' => $group,
        ]);
    }

    public function destroy(
        CourseLevel $level,
        Group $group
    ): JsonResponse {
        abort_unless($group->course_level_id === $level->id, 404);

        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'Group deleted successfully.',
        ]);
    }
}
