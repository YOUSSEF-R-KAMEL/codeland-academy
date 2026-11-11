<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_projects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('course_level_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('group_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('title');

            $table->text('description')->nullable();

            $table->string('project_url', 2048)->nullable();

            $table->string('status', 30)->default('in_progress');

            $table->decimal('score', 5, 2)->nullable();

            $table->text('instructor_feedback')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->boolean('is_featured')->default(false);

            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['course_level_id', 'status']);
            $table->index(['group_id', 'status']);
            $table->index(['is_featured', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_projects');
    }
};
