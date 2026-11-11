<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->string('email');

            $table->string('phone', 30)->nullable();

            $table->string('subject');

            $table->text('message');

            $table->string('status', 30)->default('new');

            $table->foreignId('handled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('admin_reply')->nullable();

            $table->timestamp('replied_at')->nullable();

            $table->timestamp('handled_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('handled_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
