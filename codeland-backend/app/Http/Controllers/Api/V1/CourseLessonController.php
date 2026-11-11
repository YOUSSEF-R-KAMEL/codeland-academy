<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreCourseLessonRequest;
use App\Http\Requests\Api\V1\UpdateCourseLessonRequest;
use App\Models\CourseLevel;
use App\Models\CourseLesson;
use Illuminate\Validation\Rule;

class CourseLessonController extends ApiController
{
    public function index(CourseLevel $level)
    {
        $lessons = $level->lessons()
            ->orderBy('sort_order')
            ->orderBy('lesson_number')
            ->get();

        return $this->success(
            'تم جلب محاضرات المستوى بنجاح.',
            [
                'lessons' => $lessons,
            ]
        );
    }

    public function show(CourseLevel $level, CourseLesson $lesson)
    {
        abort_unless($lesson->course_level_id === $level->id, 404);

        return $this->success(
            'تم جلب تفاصيل المحاضرة بنجاح.',
            [
                'lesson' => $lesson,
            ]
        );
    }

    public function store(
        StoreCourseLessonRequest $request,
        CourseLevel $level
    ) {
        $data = $request->validated();

        validator($data, [
            'lesson_number' => [
                Rule::unique('course_lessons', 'lesson_number')
                    ->where('course_level_id', $level->id),
            ],
        ])->validate();

        if (!empty($data['is_published']) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $lesson = $level->lessons()->create($data);

        return $this->success(
            'تم إنشاء المحاضرة بنجاح.',
            [
                'lesson' => $lesson,
            ],
            201
        );
    }

    public function update(
        UpdateCourseLessonRequest $request,
        CourseLevel $level,
        CourseLesson $lesson
    ) {
        abort_unless($lesson->course_level_id === $level->id, 404);

        $data = $request->validated();

        if (array_key_exists('lesson_number', $data)) {
            validator($data, [
                'lesson_number' => [
                    Rule::unique('course_lessons', 'lesson_number')
                        ->where('course_level_id', $level->id)
                        ->ignore($lesson->id),
                ],
            ])->validate();
        }

        if (
            !empty($data['is_published']) &&
            !$lesson->is_published &&
            empty($data['published_at'])
        ) {
            $data['published_at'] = now();
        }

        if (
            array_key_exists('is_published', $data) &&
            !$data['is_published']
        ) {
            $data['published_at'] = null;
        }

        $lesson->update($data);

        return $this->success(
            'تم تحديث المحاضرة بنجاح.',
            [
                'lesson' => $lesson->fresh(),
            ]
        );
    }

    public function destroy(CourseLevel $level, CourseLesson $lesson)
    {
        abort_unless($lesson->course_level_id === $level->id, 404);

        $lesson->delete();

        return $this->success(
            'تم حذف المحاضرة بنجاح.'
        );
    }
}
