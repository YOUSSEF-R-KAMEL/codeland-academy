<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreCourseLessonRequest;
use App\Http\Requests\Api\V1\UpdateCourseLessonRequest;
use App\Models\Course;
use App\Models\CourseLesson;
use Illuminate\Validation\Rule;

class CourseLessonController extends ApiController
{
    public function index(Course $course)
    {
        $user = request()->user();

        $this->authorizeLessonReading($course);

        $query = $course->lessons();

        if ($user->hasRole('student')) {
            $query->where('is_published', true);
        }

        $lessons = $query
            ->orderBy('sort_order')
            ->orderBy('lesson_number')
            ->get();

        return $this->success(
            'تم جلب محاضرات الكورس بنجاح.',
            [
                'lessons' => $lessons,
            ]
        );
    }

    public function show(Course $course, CourseLesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);

        $user = request()->user();

        $this->authorizeLessonReading($course);

        if ($user->hasRole('student')) {
            abort_unless($lesson->is_published, 404);
        }

        return $this->success(
            'تم جلب تفاصيل المحاضرة بنجاح.',
            [
                'lesson' => $lesson,
            ]
        );
    }

    public function store(
        StoreCourseLessonRequest $request,
        Course $course
    ) {
        $this->authorizeLessonManagement($course);

        $data = $request->validated();

        validator($data, [
            'lesson_number' => [
                Rule::unique('course_lessons', 'lesson_number')
                    ->where('course_id', $course->id),
            ],
        ])->validate();

        if (!empty($data['is_published']) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $lesson = $course->lessons()->create($data);

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
        Course $course,
        CourseLesson $lesson
    ) {
        abort_unless($lesson->course_id === $course->id, 404);

        $this->authorizeLessonManagement($course);

        $data = $request->validated();

        if (array_key_exists('lesson_number', $data)) {
            validator($data, [
                'lesson_number' => [
                    Rule::unique('course_lessons', 'lesson_number')
                        ->where('course_id', $course->id)
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

    public function destroy(Course $course, CourseLesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);

        $this->authorizeLessonManagement($course);

        $lesson->delete();

        return $this->success(
            'تم حذف المحاضرة بنجاح.'
        );
    }

    private function authorizeLessonReading(Course $course): void
    {
        $user = request()->user();

        if ($user->hasRole('admin')) {
            return;
        }

        if ($user->hasRole('student')) {
            $hasActiveEnrollment = $course->groups()
                ->whereHas('enrollments', function ($query) use ($user) {
                    $query
                        ->where('student_id', $user->id)
                        ->where('status', 'active');
                })
                ->exists();

            abort_unless($hasActiveEnrollment, 403);

            return;
        }

        if ($user->hasRole('instructor')) {
            $hasAssignedGroup = $user->instructorGroups()
                ->where('course_id', $course->id)
                ->exists();

            abort_unless($hasAssignedGroup, 403);

            return;
        }

        abort(403);
    }

    private function authorizeLessonManagement(Course $course): void
    {
        $user = request()->user();

        if ($user->hasRole('admin')) {
            return;
        }

        $hasAssignedGroup = $user->instructorGroups()
            ->where('course_id', $course->id)
            ->exists();

        abort_unless($hasAssignedGroup, 403);
    }
}
