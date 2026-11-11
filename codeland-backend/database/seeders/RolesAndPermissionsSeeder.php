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
                'manage_users' => 'Manage Users',
                'manage_roles' => 'Manage Roles',
                'manage_courses' => 'Manage Courses',
                'manage_groups' => 'Manage Groups',
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
                'view_child_progress' => 'View Child Progress',
                'view_child_payments' => 'View Child Payments',
                'view_child_attendance' => 'View Child Attendance',
                'view_payments' => 'View Payments',
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
                    'permissions' => array_keys($permissions),
                ],

                'instructor' => [
                    'name' => 'Instructor',
                    'permissions' => [
                        'manage_lessons',
                        'manage_assignments',
                        'review_submissions',
                        'manage_attendance',
                        'view_progress',
                        'ask_questions',
                    ],
                ],

                'student' => [
                    'name' => 'Student',
                    'permissions' => [
                        'view_lessons',
                        'submit_assignments',
                        'manage_projects',
                        'view_progress',
                        'ask_questions',
                        'view_payments',
                    ],
                ],

                'parent' => [
                    'name' => 'Parent',
                    'permissions' => [
                        'view_child_progress',
                        'view_child_payments',
                        'view_child_attendance',
                        'ask_questions',
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
