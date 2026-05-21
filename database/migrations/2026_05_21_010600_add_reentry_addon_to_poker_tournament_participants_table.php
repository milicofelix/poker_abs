<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poker_tournament_participants', function (Blueprint $table): void {
            if (! Schema::hasColumn('poker_tournament_participants', 'reentries_count')) {
                $table->unsignedTinyInteger('reentries_count')->default(0)->after('prize_amount');
            }

            if (! Schema::hasColumn('poker_tournament_participants', 'addons_count')) {
                $table->unsignedTinyInteger('addons_count')->default(0)->after('reentries_count');
            }

            if (! Schema::hasColumn('poker_tournament_participants', 'reentered_at')) {
                $table->timestamp('reentered_at')->nullable()->after('registered_at');
            }

            if (! Schema::hasColumn('poker_tournament_participants', 'addon_taken_at')) {
                $table->timestamp('addon_taken_at')->nullable()->after('reentered_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('poker_tournament_participants', function (Blueprint $table): void {
            foreach (['addon_taken_at', 'reentered_at', 'addons_count', 'reentries_count'] as $column) {
                if (Schema::hasColumn('poker_tournament_participants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
