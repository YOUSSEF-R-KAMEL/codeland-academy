<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_badges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('badge_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('awarded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('awarded_at');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'badge_id']);

            $table->index(['student_id', 'awarded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_badges');
    }
};
