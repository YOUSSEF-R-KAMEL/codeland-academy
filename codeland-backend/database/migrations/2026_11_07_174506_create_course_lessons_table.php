<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_lessons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_level_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->unsignedSmallInteger('lesson_number');
            $table->unsignedSmallInteger('duration_minutes')->nullable();

            $table->string('recording_url', 2048)->nullable();

            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_level_id', 'lesson_number']);

            $table->index([
                'course_level_id',
                'is_published',
                'sort_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_lessons');
    }
};
