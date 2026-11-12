<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_level_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('group_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->timestamp('due_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['course_level_id', 'is_active']);
            $table->index(['group_id', 'is_active']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
