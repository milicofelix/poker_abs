<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poker_tournaments', function (Blueprint $table): void {
            if (! Schema::hasColumn('poker_tournaments', 'is_final_table')) {
                $table->boolean('is_final_table')->default(false)->after('paid_places_count');
            }

            if (! Schema::hasColumn('poker_tournaments', 'final_table_started_at')) {
                $table->timestamp('final_table_started_at')->nullable()->after('is_final_table');
            }

            if (! Schema::hasColumn('poker_tournaments', 'final_table_seat_map')) {
                $table->json('final_table_seat_map')->nullable()->after('final_table_started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('poker_tournaments', function (Blueprint $table): void {
            if (Schema::hasColumn('poker_tournaments', 'final_table_seat_map')) {
                $table->dropColumn('final_table_seat_map');
            }

            if (Schema::hasColumn('poker_tournaments', 'final_table_started_at')) {
                $table->dropColumn('final_table_started_at');
            }

            if (Schema::hasColumn('poker_tournaments', 'is_final_table')) {
                $table->dropColumn('is_final_table');
            }
        });
    }
};
