<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreLessonResourceRequest;
use App\Http\Requests\Api\V1\UpdateLessonResourceRequest;
use App\Models\CourseLesson;
use App\Models\LessonResource;

class LessonResourceController extends ApiController
{
    public function index(CourseLesson $lesson)
    {
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

        $resource->delete();

        return $this->success(
            'تم حذف المورد بنجاح.'
        );
    }
}
