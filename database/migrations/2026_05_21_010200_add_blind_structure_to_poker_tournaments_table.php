<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poker_tournaments', function (Blueprint $table): void {
            $table->unsignedInteger('current_blind_level')->default(1)->after('max_players');
            $table->unsignedInteger('small_blind')->default(25)->after('current_blind_level');
            $table->unsignedInteger('big_blind')->default(50)->after('small_blind');
            $table->unsignedInteger('blind_level_minutes')->default(10)->after('big_blind');
            $table->timestamp('next_blind_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('poker_tournaments', function (Blueprint $table): void {
            $table->dropColumn([
                'current_blind_level',
                'small_blind',
                'big_blind',
                'blind_level_minutes',
                'next_blind_at',
            ]);
        });
    }
};
