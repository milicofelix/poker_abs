<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('poker_bankroll')->default(10000)->after('password');
        });

        Schema::table('poker_table_players', function (Blueprint $table): void {
            $table->unsignedInteger('buy_in_amount')->nullable()->after('stack');
            $table->timestamp('buy_in_paid_at')->nullable()->after('buy_in_amount');
        });
    }

    public function down(): void
    {
        Schema::table('poker_table_players', function (Blueprint $table): void {
            $table->dropColumn(['buy_in_amount', 'buy_in_paid_at']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('poker_bankroll');
        });
    }
};
