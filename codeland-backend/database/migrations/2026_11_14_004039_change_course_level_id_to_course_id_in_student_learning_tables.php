<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Student Progress
        Schema::table('student_progress', function (Blueprint $table) {
            $table->dropForeign(['course_level_id']);

            $table->dropUnique(
                'student_progress_student_id_course_level_id_unique'
            );

            $table->dropColumn('course_level_id');
        });

        Schema::table('student_progress', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->constrained('courses')
                ->restrictOnDelete();

            $table->unique(['student_id', 'course_id']);
        });

        // Student Evaluations
        // The old course_level_id foreign key was already dropped
        // during a previous failed migration attempt.
        Schema::table('student_evaluations', function (Blueprint $table) {
            $table->index('student_id');

            $table->dropIndex(
                'student_evaluations_student_id_course_level_id_index'
            );

            $table->dropColumn('course_level_id');
        });

        Schema::table('student_evaluations', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->constrained('courses')
                ->restrictOnDelete();

            $table->index(['student_id', 'course_id']);
        });

        // Certificates
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['course_level_id']);

            $table->index('student_id');

            $table->dropUnique(
                'certificates_student_id_course_level_id_unique'
            );

            $table->dropColumn('course_level_id');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->constrained('courses')
                ->restrictOnDelete();

            $table->unique(['student_id', 'course_id']);
        });
    }

    public function down(): void
    {
        // Student Progress
        Schema::table('student_progress', function (Blueprint $table) {
            $table->dropForeign(['course_id']);

            $table->dropUnique(
                'student_progress_student_id_course_id_unique'
            );

            $table->dropColumn('course_id');
        });

        Schema::table('student_progress', function (Blueprint $table) {
            $table->foreignId('course_level_id')
                ->constrained('course_levels')
                ->restrictOnDelete();

            $table->unique(['student_id', 'course_level_id']);
        });

        // Student Evaluations
        Schema::table('student_evaluations', function (Blueprint $table) {
            $table->dropForeign(['course_id']);

            $table->dropIndex(
                'student_evaluations_student_id_course_id_index'
            );

            $table->dropColumn('course_id');
        });

        Schema::table('student_evaluations', function (Blueprint $table) {
            $table->foreignId('course_level_id')
                ->constrained('course_levels')
                ->restrictOnDelete();

            $table->index(['student_id', 'course_level_id']);
        });

        // Certificates
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['course_id']);

            $table->dropUnique(
                'certificates_student_id_course_id_unique'
            );

            $table->dropColumn('course_id');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('course_level_id')
                ->constrained('course_levels')
                ->restrictOnDelete();

            $table->unique(['student_id', 'course_level_id']);
        });
    }
};
