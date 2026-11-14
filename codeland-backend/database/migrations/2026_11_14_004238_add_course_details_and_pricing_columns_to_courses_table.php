<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_weeks')->nullable();

            $table->unsignedTinyInteger('sessions_per_week')->nullable();

            $table->unsignedSmallInteger('session_duration_minutes')->nullable();

            $table->decimal('monthly_price', 10, 2)->nullable();

            $table->decimal('full_price', 10, 2)->nullable();

            $table->char('currency', 3)->default('EGP');
        });

        DB::table('courses')
            ->whereIn('slug', [
                'scratch-game-development',
                'scratch_game_development',
            ])
            ->update([
                'duration_weeks' => 8,
                'sessions_per_week' => 1,
                'session_duration_minutes' => 180,
                'monthly_price' => 420,
                'full_price' => 800,
            ]);

        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_weeks')->nullable(false)->change();

            $table->unsignedTinyInteger('sessions_per_week')->nullable(false)->change();

            $table->unsignedSmallInteger('session_duration_minutes')->nullable(false)->change();

            $table->decimal('full_price', 10, 2)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'duration_weeks',
                'sessions_per_week',
                'session_duration_minutes',
                'monthly_price',
                'full_price',
                'currency',
            ]);
        });
    }
};
