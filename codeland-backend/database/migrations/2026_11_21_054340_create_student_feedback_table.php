<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_feedback', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('type', [
                'suggestion',
                'complaint',
                'inquiry',
            ]);

            $table->string('subject');
            $table->text('message');

            $table->enum('status', [
                'new',
                'in_review',
                'resolved',
                'rejected',
            ])->default('new');

            $table->text('admin_reply')->nullable();

            $table->foreignId('replied_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('replied_at')->nullable();

            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_feedback');
    }
};
