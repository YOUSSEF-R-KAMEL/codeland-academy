<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            [
                'title' => 'Computer & Logic Basics',
                'slug' => 'computer-logic-basics',
                'description' => 'An introductory course covering computer fundamentals, digital skills, logical thinking, algorithms, and basic programming concepts.',
                'duration_weeks' => 4,
                'sessions_per_week' => 1,
                'session_duration_minutes' => 180,
                'monthly_price' => null,
                'full_price' => 360,
                'currency' => 'EGP',
                'is_published' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Scratch Game Development',
                'slug' => 'scratch-game-development',
                'description' => 'Learn programming fundamentals by creating interactive games using Scratch.',
                'duration_weeks' => 8,
                'sessions_per_week' => 1,
                'session_duration_minutes' => 180,
                'monthly_price' => 420,
                'full_price' => 800,
                'currency' => 'EGP',
                'is_published' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Python Programming',
                'slug' => 'python-programming',
                'description' => 'Learn Python programming fundamentals, problem solving, data structures, and functions through practical projects.',
                'duration_weeks' => 12,
                'sessions_per_week' => 1,
                'session_duration_minutes' => 180,
                'monthly_price' => 500,
                'full_price' => 1350,
                'currency' => 'EGP',
                'is_published' => true,
                'sort_order' => 3,
            ],
            [
                'title' => 'Web Design with HTML & CSS',
                'slug' => 'web-design-html-css',
                'description' => 'Learn to build responsive websites using HTML and CSS for desktop and mobile screens.',
                'duration_weeks' => 12,
                'sessions_per_week' => 1,
                'session_duration_minutes' => 180,
                'monthly_price' => 540,
                'full_price' => 1450,
                'currency' => 'EGP',
                'is_published' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($courses as $courseData) {
            Course::withTrashed()->updateOrCreate(
                ['slug' => $courseData['slug']],
                array_merge($courseData, ['deleted_at' => null])
            );
        }
    }
}
