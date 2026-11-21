<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $permissions = [
                'submit_projects' => 'Submit Projects',
                'manage_users' => 'Manage Users',
                'manage_roles' => 'Manage Roles',
                'manage_courses' => 'Manage Courses',
                'manage_groups' => 'Manage Groups',
                'manage_sessions' => 'Manage Class Sessions',
                'manage_lessons' => 'Manage Lessons',
                'manage_assignments' => 'Manage Assignments',
                'review_submissions' => 'Review Submissions',
                'manage_attendance' => 'Manage Attendance',
                'manage_payments' => 'Manage Payments',
                'view_reports' => 'View Reports',
                'view_lessons' => 'View Lessons',
                'submit_assignments' => 'Submit Assignments',
                'manage_projects' => 'Manage Projects',
                'view_progress' => 'View Progress',
                'ask_questions' => 'Ask Questions',
                'view_payments' => 'View Payments',

                // Evaluations & Platform Feedback
                'submit_course_evaluations' => 'Submit Course Evaluations',
                'submit_platform_feedback' => 'Submit Platform Feedback',
                'manage_evaluations' => 'Manage Evaluations',

                // Student Feedback
                'submit_student_feedback' => 'Submit Student Feedback',
                'manage_student_feedback' => 'Manage Student Feedback',
            ];

            $permissionModels = [];

            foreach ($permissions as $slug => $name) {
                $permissionModels[$slug] = Permission::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'description' => null,
                    ]
                );
            }

            $roles = [
                'admin' => [
                    'name' => 'Administrator',
                    'permissions' => array_values(array_diff(
                        array_keys($permissions),
                        ['submit_student_feedback']
                    )),
                ],

                'instructor' => [
                    'name' => 'Instructor',
                    'permissions' => [
                        'manage_lessons',
                        'manage_assignments',
                        'review_submissions',
                        'manage_attendance',
                        'manage_sessions',
                        'manage_projects',
                        'view_progress',
                        'ask_questions',
                    ],
                ],

                'student' => [
                    'name' => 'Student',
                    'permissions' => [
                        'view_lessons',
                        'submit_assignments',
                        'submit_projects',
                        'view_progress',
                        'ask_questions',
                        'view_payments',

                        // Evaluations & Platform Feedback
                        'submit_course_evaluations',
                        'submit_platform_feedback',

                        // Student Feedback
                        'submit_student_feedback',
                    ],
                ],
            ];

            foreach ($roles as $slug => $roleData) {
                $role = Role::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $roleData['name'],
                        'description' => null,
                    ]
                );

                $role->permissions()->sync(
                    collect($roleData['permissions'])
                        ->map(fn($permission) => $permissionModels[$permission]->id)
                        ->all()
                );
            }
        });
    }
}
