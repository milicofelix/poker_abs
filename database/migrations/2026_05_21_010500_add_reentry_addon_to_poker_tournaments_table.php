<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poker_tournaments', function (Blueprint $table): void {
            if (! Schema::hasColumn('poker_tournaments', 'allow_reentry')) {
                $table->boolean('allow_reentry')->default(true)->after('final_table_seat_map');
            }

            if (! Schema::hasColumn('poker_tournaments', 'max_reentries_per_player')) {
                $table->unsignedTinyInteger('max_reentries_per_player')->default(1)->after('allow_reentry');
            }

            if (! Schema::hasColumn('poker_tournaments', 'reentry_buy_in')) {
                $table->unsignedInteger('reentry_buy_in')->nullable()->after('max_reentries_per_player');
            }

            if (! Schema::hasColumn('poker_tournaments', 'reentry_stack')) {
                $table->unsignedInteger('reentry_stack')->nullable()->after('reentry_buy_in');
            }

            if (! Schema::hasColumn('poker_tournaments', 'addon_enabled')) {
                $table->boolean('addon_enabled')->default(true)->after('reentry_stack');
            }

            if (! Schema::hasColumn('poker_tournaments', 'addon_buy_in')) {
                $table->unsignedInteger('addon_buy_in')->nullable()->after('addon_enabled');
            }

            if (! Schema::hasColumn('poker_tournaments', 'addon_stack')) {
                $table->unsignedInteger('addon_stack')->nullable()->after('addon_buy_in');
            }

            if (! Schema::hasColumn('poker_tournaments', 'addon_available_until_blind_level')) {
                $table->unsignedSmallInteger('addon_available_until_blind_level')->nullable()->after('addon_stack');
            }
        });
    }

    public function down(): void
    {
        Schema::table('poker_tournaments', function (Blueprint $table): void {
            foreach ([
                'addon_available_until_blind_level',
                'addon_stack',
                'addon_buy_in',
                'addon_enabled',
                'reentry_stack',
                'reentry_buy_in',
                'max_reentries_per_player',
                'allow_reentry',
            ] as $column) {
                if (Schema::hasColumn('poker_tournaments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
