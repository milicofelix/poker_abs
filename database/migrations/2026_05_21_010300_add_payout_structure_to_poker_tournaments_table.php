<?php

use App\Models\Poker\PokerTournament;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poker_tournaments', function (Blueprint $table): void {
            if (! Schema::hasColumn('poker_tournaments', 'payout_structure')) {
                $table->json('payout_structure')->nullable()->after('next_blind_at');
            }

            if (! Schema::hasColumn('poker_tournaments', 'paid_places_count')) {
                $table->unsignedTinyInteger('paid_places_count')->default(1)->after('payout_structure');
            }
        });

        PokerTournament::query()
            ->whereNull('payout_structure')
            ->update([
                'payout_structure' => json_encode(PokerTournament::DEFAULT_PAYOUT_STRUCTURE),
                'paid_places_count' => count(PokerTournament::DEFAULT_PAYOUT_STRUCTURE),
            ]);
    }

    public function down(): void
    {
        Schema::table('poker_tournaments', function (Blueprint $table): void {
            if (Schema::hasColumn('poker_tournaments', 'paid_places_count')) {
                $table->dropColumn('paid_places_count');
            }

            if (Schema::hasColumn('poker_tournaments', 'payout_structure')) {
                $table->dropColumn('payout_structure');
            }
        });
    }
};
