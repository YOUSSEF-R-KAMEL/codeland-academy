<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCourseLevelRequest;
use App\Http\Requests\Api\V1\UpdateCourseLevelRequest;
use App\Models\Course;
use App\Models\CourseLevel;
use Illuminate\Validation\Rule;

class CourseLevelController extends ApiController
{
    public function index(Course $course)
    {
        $levels = $course->levels()
            ->orderBy('sort_order')
            ->get();

        return $this->success(
            'تم جلب مستويات الكورس بنجاح.',
            [
                'levels' => $levels,
            ]
        );
    }

    public function show(Course $course, CourseLevel $level)
    {
        abort_unless($level->course_id === $course->id, 404);

        return $this->success(
            'تم جلب تفاصيل المستوى بنجاح.',
            [
                'level' => $level,
            ]
        );
    }

    public function store(
        StoreCourseLevelRequest $request,
        Course $course
    ) {
        $data = $request->validated();

        validator($data, [
            'slug' => [
                Rule::unique('course_levels', 'slug')
                    ->where('course_id', $course->id),
            ],
        ])->validate();

        $level = $course->levels()->create($data);

        return $this->success(
            'تم إنشاء المستوى بنجاح.',
            [
                'level' => $level,
            ],
            201
        );
    }

    public function update(
        UpdateCourseLevelRequest $request,
        Course $course,
        CourseLevel $level
    ) {
        abort_unless($level->course_id === $course->id, 404);

        $data = $request->validated();

        if (array_key_exists('slug', $data)) {
            validator($data, [
                'slug' => [
                    Rule::unique('course_levels', 'slug')
                        ->where('course_id', $course->id)
                        ->ignore($level->id),
                ],
            ])->validate();
        }

        $level->update($data);

        return $this->success(
            'تم تحديث المستوى بنجاح.',
            [
                'level' => $level->fresh(),
            ]
        );
    }

    public function destroy(Course $course, CourseLevel $level)
    {
        abort_unless($level->course_id === $course->id, 404);

        $level->delete();

        return $this->success(
            'تم حذف المستوى بنجاح.'
        );
    }
}
