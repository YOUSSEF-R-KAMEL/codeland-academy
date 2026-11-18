<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('attendances', 'is_present')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->boolean('is_present')->after('check_number');
            });
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->index(
                ['student_id', 'class_session_id', 'is_present'],
                'attendances_student_session_present_index'
            );
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(
                'attendances_student_id_class_session_id_status_index'
            );

            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('status', 20)->after('check_number');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index(
                ['student_id', 'class_session_id', 'status']
            );

            $table->dropIndex(
                'attendances_student_session_present_index'
            );

            $table->dropColumn('is_present');
        });
    }
};
