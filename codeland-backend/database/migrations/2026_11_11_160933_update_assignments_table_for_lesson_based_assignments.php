<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign('assignments_course_lesson_id_foreign');

            $table->dropIndex('assignments_course_lesson_id_foreign');

            $table->unsignedBigInteger('course_lesson_id')
                ->nullable(false)
                ->change();

            $table->unique('course_lesson_id');

            $table->foreign('course_lesson_id')
                ->references('id')
                ->on('course_lessons')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign(['course_lesson_id']);

            $table->dropUnique(['course_lesson_id']);

            $table->unsignedBigInteger('course_lesson_id')
                ->nullable()
                ->change();

            $table->foreign('course_lesson_id')
                ->references('id')
                ->on('course_lessons')
                ->nullOnDelete();
        });
    }
};
