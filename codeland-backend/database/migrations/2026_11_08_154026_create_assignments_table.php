<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('group_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('course_lesson_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('title');

            $table->longText('instructions');

            $table->string('resource_url', 2048)->nullable();

            $table->timestamp('published_at')->nullable();

            $table->timestamp('due_at')->nullable();

            $table->boolean('is_published')->default(false);

            $table->timestamps();

            $table->softDeletes();

            $table->index(['group_id', 'is_published']);
            $table->index(['created_by', 'created_at']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
