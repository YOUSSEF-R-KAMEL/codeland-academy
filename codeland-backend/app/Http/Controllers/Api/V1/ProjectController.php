<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProjectRequest;
use App\Http\Requests\Api\V1\UpdateProjectRequest;
use App\Models\Group;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole('admin') || $user->hasRole('instructor'),
            403
        );

        $query = Project::query()
            ->with([
                'courseLevel',
                'group',
                'creator:id,name',
            ]);

        if ($user->hasRole('instructor') && !$user->hasRole('admin')) {
            $groupIds = $user->instructorGroups()
                ->pluck('groups.id');

            $query->whereIn('group_id', $groupIds);
        }

        if ($request->filled('course_level_id')) {
            $query->where(
                'course_level_id',
                $request->integer('course_level_id')
            );
        }

        if ($request->filled('group_id')) {
            $query->where(
                'group_id',
                $request->integer('group_id')
            );
        }

        if ($request->filled('is_active')) {
            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        return response()->json([
            'data' => $query
                ->latest()
                ->paginate(15),
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole('admin') || $user->hasRole('instructor'),
            403
        );

        $data = $request->validated();

        if ($user->hasRole('instructor') && !$user->hasRole('admin')) {
            abort_if(
                empty($data['group_id']),
                403,
                'Instructors can only create projects for their assigned groups.'
            );

            abort_unless(
                $user->instructorGroups()
                    ->whereKey($data['group_id'])
                    ->exists(),
                403
            );
        }

        $project = DB::transaction(function () use ($data, $user) {
            return Project::create([
                ...$data,
                'created_by' => $user->id,
            ]);
        });

        return response()->json([
            'message' => 'Project created successfully.',
            'data' => $project->load([
                'courseLevel',
                'group',
                'creator:id,name',
            ]),
        ], 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProjectAccess($request, $project);

        return response()->json([
            'data' => $project->load([
                'courseLevel',
                'group',
                'creator:id,name',
            ]),
        ]);
    }


    public function destroy(
        Request $request,
        Project $project
    ): JsonResponse {
        $this->authorizeProjectAccess($request, $project);

        abort_if(
            $project->studentProjects()->exists(),
            409,
            'This project has student submissions and cannot be deleted.'
        );

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }

    private function authorizeProjectAccess(
        Request $request,
        Project $project
    ): void {
        $user = $request->user();

        abort_unless(
            $user->hasRole('admin') || $user->hasRole('instructor'),
            403
        );

        if (
            $user->hasRole('instructor')
            && !$user->hasRole('admin')
        ) {
            abort_if($project->group_id === null, 403);

            abort_unless(
                $user->instructorGroups()
                    ->whereKey($project->group_id)
                    ->exists(),
                403
            );
        }
    }


    public function update(
        UpdateProjectRequest $request,
        Project $project
    ): JsonResponse {
        $this->authorizeProjectAccess($request, $project);

        $user = $request->user();
        $data = $request->validated();
        unset($data['created_by']);
        $newCourseLevelId = $data['course_level_id']
            ?? $project->course_level_id;

        $newGroupId = array_key_exists('group_id', $data)
            ? $data['group_id']
            : $project->group_id;

        if ($newGroupId !== null) {
            $group = Group::query()->findOrFail($newGroupId);

            abort_unless(
                (int) $group->course_level_id === (int) $newCourseLevelId,
                422,
                'The selected group does not belong to the selected course level.'
            );
        }

        if (
            $user->hasRole('instructor')
            && !$user->hasRole('admin')
        ) {
            abort_if($newGroupId === null, 403);

            abort_unless(
                $user->instructorGroups()
                    ->whereKey($newGroupId)
                    ->exists(),
                403
            );
        }

        $scopeChanged =
            (int) $newCourseLevelId !== (int) $project->course_level_id
            || (
                $newGroupId === null
                ? null
                : (int) $newGroupId
            ) !== (
                $project->group_id === null
                ? null
                : (int) $project->group_id
            );

        if ($scopeChanged) {
            abort_if(
                $project->studentProjects()->exists(),
                409,
                'The project scope cannot be changed after student submissions exist.'
            );
        }

        $project->update($data);

        return response()->json([
            'message' => 'Project updated successfully.',
            'data' => $project->fresh()->load([
                'courseLevel',
                'group',
                'creator:id,name',
            ]),
        ]);
    }

}
