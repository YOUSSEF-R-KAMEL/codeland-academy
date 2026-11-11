<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')
                ->nullable()
                ->after('score');

            $table->foreignId('feedback_template_id')
                ->nullable()
                ->after('rating')
                ->constrained('feedback_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropForeign(['feedback_template_id']);
            $table->dropColumn([
                'feedback_template_id',
                'rating',
            ]);
        });
    }
};
