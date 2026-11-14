<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('course_levels');
    }

    public function down(): void
    {
        Schema::create('course_levels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('slug');

            $table->text('description')->nullable();

            $table->unsignedSmallInteger('duration_weeks');
            $table->unsignedSmallInteger('sessions_per_week')->default(1);
            $table->unsignedSmallInteger('session_duration_minutes');

            $table->decimal('monthly_price', 10, 2)->nullable();
            $table->decimal('full_price', 10, 2);

            $table->string('currency', 3)->default('EGP');

            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_id', 'slug']);
            $table->index(['course_id', 'is_published', 'sort_order']);
        });
    }
};
