<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('group_instructors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('group_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('instructor_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->unique(['group_id', 'instructor_id']);

            $table->index(['instructor_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_instructors');
    }
};
