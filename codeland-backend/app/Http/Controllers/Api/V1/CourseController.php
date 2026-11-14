<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreCourseRequest;
use App\Http\Requests\Api\V1\UpdateCourseRequest;
use App\Models\Course;

class CourseController extends ApiController
{
    public function index()
    {
        $courses = Course::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get([
                'id',
                'title',
                'slug',
                'description',
                'thumbnail_url',
                'duration_weeks',
                'sessions_per_week',
                'session_duration_minutes',
                'full_price',
                'monthly_price',
                'currency',
                'sort_order',
            ]);

        return $this->success(
            'تم جلب الكورسات بنجاح.',
            [
                'courses' => $courses,
            ]
        );
    }

    public function show(string $slug)
    {
        $course = Course::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail([
                'id',
                'title',
                'slug',
                'description',
                'thumbnail_url',
                'duration_weeks',
                'sessions_per_week',
                'session_duration_minutes',
                'full_price',
                'monthly_price',
                'currency',
            ]);

        return $this->success(
            'تم جلب تفاصيل الكورس بنجاح.',
            [
                'course' => $course,
            ]
        );
    }

    public function store(StoreCourseRequest $request)
    {
        $course = Course::create($request->validated());

        return $this->success(
            'تم إنشاء الكورس بنجاح.',
            [
                'course' => $course,
            ],
            201
        );
    }

    public function update(
        UpdateCourseRequest $request,
        Course $course
    ) {
        $course->update($request->validated());

        return $this->success(
            'تم تحديث الكورس بنجاح.',
            [
                'course' => $course->fresh(),
            ]
        );
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return $this->success(
            'تم حذف الكورس بنجاح.'
        );
    }
}
