<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assignment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('submission_url')->nullable();

            $table->text('submission_notes')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->string('status', 30)->default('not_submitted');

            $table->decimal('score', 5, 2)->nullable();

            $table->text('instructor_feedback')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->unique(['assignment_id', 'student_id']);

            $table->index(['student_id', 'status']);
            $table->index(['assignment_id', 'status']);
            $table->index('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
