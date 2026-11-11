<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_level_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('name');
            $table->string('code')->unique();

            $table->unsignedSmallInteger('capacity')->default(8);

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->string('status', 30)->default('planned');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_level_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
