<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Remove Parent role assignments and permissions.
        $parentRole = DB::table('roles')
            ->where('slug', 'parent')
            ->first();

        if ($parentRole) {
            DB::table('role_user')
                ->where('role_id', $parentRole->id)
                ->delete();

            DB::table('permission_role')
                ->where('role_id', $parentRole->id)
                ->delete();

            DB::table('roles')
                ->where('id', $parentRole->id)
                ->delete();
        }

        // Remove the Parent-Student relationship table.
        Schema::dropIfExists('parent_student');

        // Keep consent records, but remove their dependency on Parent accounts.
        if (Schema::hasTable('student_consents')) {
            Schema::table('student_consents', function (Blueprint $table) {
                $table->dropForeign(['parent_id']);
                $table->dropIndex(['parent_id', 'consent_type']);
                $table->dropColumn('parent_id');

                $table->string('consenter_name')->nullable();
                $table->string('consenter_contact')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Restore Parent-Student relationship table.
        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('relationship')->nullable();
            $table->boolean('is_primary_contact')->default(false);
            $table->timestamps();

            $table->unique(['parent_id', 'student_id']);
            $table->index('student_id');
        });

        // Restore consent table structure.
        if (Schema::hasTable('student_consents')) {
            Schema::table('student_consents', function (Blueprint $table) {
                $table->dropColumn([
                    'consenter_name',
                    'consenter_contact',
                ]);

                $table->foreignId('parent_id')
                    ->nullable()
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->index(['parent_id', 'consent_type']);
            });
        }

        // Restore Parent role and permissions.
        $parentPermissions = [
            'view_child_progress' => 'View Child Progress',
            'view_child_payments' => 'View Child Payments',
            'view_child_attendance' => 'View Child Attendance',
        ];

        $permissionIds = [];

        foreach ($parentPermissions as $slug => $name) {
            $existing = DB::table('permissions')
                ->where('slug', $slug)
                ->first();

            if ($existing) {
                $permissionIds[] = $existing->id;
            } else {
                $permissionIds[] = DB::table('permissions')
                    ->insertGetId([
                        'name' => $name,
                        'slug' => $slug,
                        'description' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        }

        $parentRoleId = DB::table('roles')->insertGetId([
            'name' => 'Parent',
            'slug' => 'parent',
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->insert([
                'role_id' => $parentRoleId,
                'permission_id' => $permissionId,
            ]);
        }

        $askQuestionsId = DB::table('permissions')
            ->where('slug', 'ask_questions')
            ->value('id');

        if ($askQuestionsId) {
            DB::table('permission_role')->insert([
                'role_id' => $parentRoleId,
                'permission_id' => $askQuestionsId,
            ]);
        }
    }
};
