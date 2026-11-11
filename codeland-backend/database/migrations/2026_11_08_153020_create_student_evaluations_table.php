<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_evaluations', function (Blueprint $table) {
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

            $table->foreignId('instructor_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('understanding_score')->nullable();

            $table->unsignedTinyInteger('problem_solving_score')->nullable();

            $table->unsignedTinyInteger('participation_score')->nullable();

            $table->unsignedTinyInteger('practical_work_score')->nullable();

            $table->decimal('overall_score', 5, 2)->nullable();

            $table->text('strengths')->nullable();

            $table->text('areas_to_improve')->nullable();

            $table->text('instructor_notes')->nullable();

            $table->date('evaluated_at');

            $table->timestamps();

            $table->index(['student_id', 'course_level_id']);
            $table->index(['instructor_id', 'evaluated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_evaluations');
    }
};
