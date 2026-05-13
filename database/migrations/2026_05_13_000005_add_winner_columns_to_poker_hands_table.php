<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poker_hands', function (Blueprint $table): void {
            $table->string('winner')->nullable()->after('dealer_position');
            $table->string('winner_label')->nullable()->after('winner');
            $table->string('winning_hand_name')->nullable()->after('winner_label');

            $table->index(['status', 'winner']);
        });
    }

    public function down(): void
    {
        Schema::table('poker_hands', function (Blueprint $table): void {
            $table->dropIndex(['status', 'winner']);
            $table->dropColumn(['winner', 'winner_label', 'winning_hand_name']);
        });
    }
};
