<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreLessonResourceRequest;
use App\Http\Requests\Api\V1\UpdateLessonResourceRequest;
use App\Models\CourseLesson;
use App\Models\Group;
use App\Models\LessonResource;

class LessonResourceController extends ApiController
{
    public function index(CourseLesson $lesson)
    {
        $this->authorizeLessonAccess($lesson);

        $resources = $lesson->resources()
            ->orderBy('sort_order')
            ->get();

        return $this->success(
            'تم جلب موارد المحاضرة بنجاح.',
            [
                'resources' => $resources,
            ]
        );
    }

    public function show(
        CourseLesson $lesson,
        LessonResource $resource
    ) {
        abort_unless(
            $resource->course_lesson_id === $lesson->id,
            404
        );

        $this->authorizeLessonAccess($lesson);

        return $this->success(
            'تم جلب تفاصيل المورد بنجاح.',
            [
                'resource' => $resource,
            ]
        );
    }

    public function store(
        StoreLessonResourceRequest $request,
        CourseLesson $lesson
    ) {
        $this->authorizeLessonManagement($lesson);

        $resource = $lesson->resources()->create(
            $request->validated()
        );

        return $this->success(
            'تم إضافة المورد بنجاح.',
            [
                'resource' => $resource,
            ],
            201
        );
    }

    public function update(
        UpdateLessonResourceRequest $request,
        CourseLesson $lesson,
        LessonResource $resource
    ) {
        abort_unless(
            $resource->course_lesson_id === $lesson->id,
            404
        );

        $this->authorizeLessonManagement($lesson);

        $resource->update($request->validated());

        return $this->success(
            'تم تحديث المورد بنجاح.',
            [
                'resource' => $resource->fresh(),
            ]
        );
    }

    public function destroy(
        CourseLesson $lesson,
        LessonResource $resource
    ) {
        abort_unless(
            $resource->course_lesson_id === $lesson->id,
            404
        );

        $this->authorizeLessonManagement($lesson);

        $resource->delete();

        return $this->success(
            'تم حذف المورد بنجاح.'
        );
    }

    private function authorizeLessonAccess(CourseLesson $lesson): void
    {
        $user = request()->user();

        if ($user->hasRole('admin')) {
            return;
        }

        if ($user->hasRole('student')) {
            abort_unless($lesson->is_published, 404);

            $isEnrolled = Group::query()
                ->where('course_id', $lesson->course_id)
                ->whereHas('enrollments', function ($query) use ($user) {
                    $query->where('student_id', $user->id)
                        ->where('status', 'active');
                })
                ->exists();

            abort_unless($isEnrolled, 403);

            return;
        }

        if ($user->hasRole('instructor')) {
            $hasAssignedGroup = $user->instructorGroups()
                ->where('course_id', $lesson->course_id)
                ->exists();

            abort_unless($hasAssignedGroup, 403);

            return;
        }

        abort(403);
    }

    private function authorizeLessonManagement(CourseLesson $lesson): void
    {
        $user = request()->user();

        if ($user->hasRole('admin')) {
            return;
        }

        $hasAssignedGroup = $user->instructorGroups()
            ->where('course_id', $lesson->course_id)
            ->exists();

        abort_unless($hasAssignedGroup, 403);
    }
}
