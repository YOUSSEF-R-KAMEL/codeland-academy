<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_consents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('parent_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('consent_type', 50);

            $table->string('status', 30)->default('granted');

            $table->timestamp('consented_at')->nullable();

            $table->timestamp('revoked_at')->nullable();

            $table->string('document_version', 30)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['student_id', 'consent_type', 'status']);
            $table->index(['parent_id', 'consent_type']);
            $table->index('consented_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_consents');
    }
};
