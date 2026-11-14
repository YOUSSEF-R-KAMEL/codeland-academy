<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAssignmentRequest;
use App\Http\Requests\Api\V1\UpdateAssignmentRequest;
use App\Models\Assignment;
use App\Models\CourseLesson;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Assignment::query()
            ->with([
                'courseLesson.course',
                'creator',
            ])
            ->withCount('submissions');

        if (!$user->hasRole('admin')) {
            $courseIds = Group::query()
                ->whereHas('instructors', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                })
                ->pluck('course_id');

            $query->whereHas('courseLesson', function ($query) use ($courseIds) {
                $query->whereIn('course_id', $courseIds);
            });
        }

        return response()->json([
            'data' => $query->latest()->paginate(15),
        ]);
    }

    public function show(
        Request $request,
        Assignment $assignment
    ): JsonResponse {
        $this->authorizeAssignmentAccess($request, $assignment);

        $assignment->load([
            'courseLesson.course',
            'creator',
        ])->loadCount('submissions');

        return response()->json([
            'data' => $assignment,
        ]);
    }

    public function store(StoreAssignmentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $lesson = CourseLesson::findOrFail(
            $validated['course_lesson_id']
        );

        $this->authorizeLessonAccess($request, $lesson);

        $existingAssignment = Assignment::withTrashed()
            ->where('course_lesson_id', $lesson->id)
            ->exists();

        if ($existingAssignment) {
            return response()->json([
                'message' => 'An assignment already exists for this lesson.',
            ], 409);
        }

        $validated['created_by'] = $request->user()->id;

        if (($validated['is_published'] ?? false) && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $assignment = Assignment::create($validated);

        $assignment->load([
            'courseLesson.course',
            'creator',
        ]);

        return response()->json([
            'message' => 'Assignment created successfully.',
            'data' => $assignment,
        ], 201);
    }

    public function update(
        UpdateAssignmentRequest $request,
        Assignment $assignment
    ): JsonResponse {
        $this->authorizeAssignmentAccess($request, $assignment);

        $validated = $request->validated();

        if (isset($validated['course_lesson_id'])) {
            $lesson = CourseLesson::findOrFail(
                $validated['course_lesson_id']
            );

            $this->authorizeLessonAccess($request, $lesson);

            $existingAssignment = Assignment::withTrashed()
                ->where('course_lesson_id', $lesson->id)
                ->where('id', '!=', $assignment->id)
                ->exists();

            if ($existingAssignment) {
                return response()->json([
                    'message' => 'An assignment already exists for this lesson.',
                ], 409);
            }
        }

        if (
            ($validated['is_published'] ?? $assignment->is_published)
            && empty($validated['published_at'])
            && !$assignment->published_at
        ) {
            $validated['published_at'] = now();
        }

        $assignment->update($validated);

        $assignment->load([
            'courseLesson.course',
            'creator',
        ]);

        return response()->json([
            'message' => 'Assignment updated successfully.',
            'data' => $assignment,
        ]);
    }

    public function destroy(
        Request $request,
        Assignment $assignment
    ): JsonResponse {
        $this->authorizeAssignmentAccess($request, $assignment);

        $assignment->delete();

        return response()->json([
            'message' => 'Assignment deleted successfully.',
        ]);
    }

    private function authorizeAssignmentAccess(
        Request $request,
        Assignment $assignment
    ): void {
        $this->authorizeLessonAccess(
            $request,
            $assignment->courseLesson
        );
    }

    private function authorizeLessonAccess(
        Request $request,
        CourseLesson $lesson
    ): void {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return;
        }

        $hasAccess = Group::query()
            ->where('course_id', $lesson->course_id)
            ->whereHas('instructors', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->exists();

        abort_unless($hasAccess, 403, 'You are not authorized to manage this lesson.');
    }
}
