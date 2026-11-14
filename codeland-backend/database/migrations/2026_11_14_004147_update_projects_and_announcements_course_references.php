<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Announcements
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['course_level_id']);
            $table->dropColumn('course_level_id');
        });

        // Student Projects
        Schema::table('student_projects', function (Blueprint $table) {
            $table->dropForeign(['course_level_id']);

            $table->dropIndex(
                'student_projects_course_level_id_status_index'
            );

            $table->dropColumn('course_level_id');
        });

        Schema::table('student_projects', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->constrained('courses')
                ->restrictOnDelete();

            $table->index(['course_id', 'status']);
        });

        // Projects
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['course_level_id']);

            $table->dropIndex(
                'projects_course_level_id_is_active_index'
            );

            $table->dropColumn('course_level_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->constrained('courses')
                ->restrictOnDelete();

            $table->index(['course_id', 'is_active']);
        });
    }

    public function down(): void
    {
        // Projects
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropIndex('projects_course_id_is_active_index');
            $table->dropColumn('course_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('course_level_id')
                ->constrained('course_levels')
                ->restrictOnDelete();

            $table->index(['course_level_id', 'is_active']);
        });

        // Student Projects
        Schema::table('student_projects', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropIndex('student_projects_course_id_status_index');
            $table->dropColumn('course_id');
        });

        Schema::table('student_projects', function (Blueprint $table) {
            $table->foreignId('course_level_id')
                ->constrained('course_levels')
                ->restrictOnDelete();

            $table->index(['course_level_id', 'status']);
        });

        // Announcements
        Schema::table('announcements', function (Blueprint $table) {
            $table->foreignId('course_level_id')
                ->nullable()
                ->constrained('course_levels')
                ->nullOnDelete();
        });
    }
};
