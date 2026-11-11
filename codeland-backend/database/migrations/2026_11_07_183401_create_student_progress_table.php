<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_progress', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('course_level_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedSmallInteger('completed_lessons')->default(0);

            $table->unsignedSmallInteger('total_lessons')->default(0);

            $table->decimal('progress_percentage', 5, 2)->default(0);

            $table->string('status', 30)->default('not_started');

            $table->timestamp('last_activity_at')->nullable();

            $table->text('instructor_notes')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'course_level_id']);

            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_progress');
    }
};
