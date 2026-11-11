<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_session_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('check_number');

            $table->string('status', 20);

            $table->timestamp('marked_at');

            $table->foreignId('marked_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique([
                'class_session_id',
                'student_id',
                'check_number',
            ]);

            $table->index([
                'student_id',
                'class_session_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
