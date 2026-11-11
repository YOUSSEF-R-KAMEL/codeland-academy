<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssignGroupInstructorRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Api\V1\UpdateGroupInstructorRequest;

class GroupInstructorController extends Controller
{
    public function index(Group $group): JsonResponse
    {
        $instructors = $group->instructors()
            ->with('roles')
            ->orderBy('users.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $instructors,
        ]);
    }

    public function store(
        AssignGroupInstructorRequest $request,
        Group $group
    ): JsonResponse {
        $instructor = User::findOrFail(
            $request->validated('instructor_id')
        );

        if (!$instructor->hasRole('instructor')) {
            return response()->json([
                'success' => false,
                'message' => 'The selected user is not an instructor.',
            ], 422);
        }

        if ($group->instructors()->where('users.id', $instructor->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'The instructor is already assigned to this group.',
            ], 422);
        }

        $isPrimary = $request->boolean('is_primary');

        DB::transaction(function () use ($group, $instructor, $isPrimary) {
            if ($isPrimary) {
                DB::table('group_instructors')
                    ->where('group_id', $group->id)
                    ->update(['is_primary' => false]);
            }

            $group->instructors()->attach($instructor->id, [
                'is_primary' => $isPrimary,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Instructor assigned successfully.',
            'data' => $group->instructors()
                ->where('users.id', $instructor->id)
                ->first(),
        ], 201);
    }

    public function destroy(Group $group, User $instructor): JsonResponse
    {
        $exists = $group->instructors()
            ->where('users.id', $instructor->id)
            ->exists();

        abort_unless($exists, 404);

        $group->instructors()->detach($instructor->id);

        return response()->json([
            'success' => true,
            'message' => 'Instructor removed successfully.',
        ]);
    }
    public function updatePrimary(
        UpdateGroupInstructorRequest $request,
        Group $group,
        User $instructor
    ): JsonResponse {
        $exists = $group->instructors()
            ->where('users.id', $instructor->id)
            ->exists();

        abort_unless($exists, 404);

        $isPrimary = $request->boolean('is_primary');

        DB::transaction(function () use ($group, $instructor, $isPrimary) {
            if ($isPrimary) {
                DB::table('group_instructors')
                    ->where('group_id', $group->id)
                    ->update(['is_primary' => false]);
            }

            $group->instructors()->updateExistingPivot($instructor->id, [
                'is_primary' => $isPrimary,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Instructor primary status updated successfully.',
            'data' => $group->instructors()
                ->where('users.id', $instructor->id)
                ->first(),
        ]);
    }
}
