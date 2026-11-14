<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('course_lessons', function (Blueprint $table) {
            $table->dropForeign(['course_level_id']);

            $table->dropUnique(
                'course_lessons_course_level_id_lesson_number_unique'
            );

            $table->dropIndex(
                'course_lessons_course_level_id_is_published_sort_order_index'
            );

            $table->dropColumn('course_level_id');
        });

        Schema::table('course_lessons', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnDelete();

            $table->unique(['course_id', 'lesson_number']);

            $table->index([
                'course_id',
                'is_published',
                'sort_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('course_lessons', function (Blueprint $table) {
            $table->dropForeign(['course_id']);

            $table->dropUnique(
                'course_lessons_course_id_lesson_number_unique'
            );

            $table->dropIndex(
                'course_lessons_course_id_is_published_sort_order_index'
            );

            $table->dropColumn('course_id');
        });

        Schema::table('course_lessons', function (Blueprint $table) {
            $table->foreignId('course_level_id')
                ->constrained('course_levels')
                ->cascadeOnDelete();

            $table->unique([
                'course_level_id',
                'lesson_number',
            ]);

            $table->index([
                'course_level_id',
                'is_published',
                'sort_order',
            ]);
        });
    }
};
