<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('title');

            $table->text('content');

            $table->string('target_type', 30)->default('all');

            $table->foreignId('course_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('course_level_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('group_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('student_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('is_published')->default(false);

            $table->timestamp('published_at')->nullable();

            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index(['is_published', 'published_at']);
            $table->index(['target_type', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
