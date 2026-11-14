<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropForeign(['course_level_id']);
            $table->dropIndex('groups_course_level_id_status_index');
            $table->dropColumn('course_level_id');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->constrained('courses')
                ->restrictOnDelete();

            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropIndex('groups_course_id_status_index');
            $table->dropColumn('course_id');
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->foreignId('course_level_id')
                ->constrained('course_levels')
                ->restrictOnDelete();

            $table->index(['course_level_id', 'status']);
        });
    }
};
