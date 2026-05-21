<?php

use App\Models\Poker\PokerTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poker_tables', function (Blueprint $table): void {
            $table->unsignedInteger('buy_in')->default(PokerTable::DEFAULT_BUY_IN)->after('big_blind');
        });
    }

    public function down(): void
    {
        Schema::table('poker_tables', function (Blueprint $table): void {
            $table->dropColumn('buy_in');
        });
    }
};
