<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();

            $table->string('certificate_number')->unique();

            $table->foreignId('student_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('course_level_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('issued_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->date('issued_at');

            $table->string('certificate_url', 2048)->nullable();

            $table->string('status', 20)->default('valid');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'course_level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
