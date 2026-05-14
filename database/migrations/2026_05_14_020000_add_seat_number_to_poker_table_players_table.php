<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poker_table_players', function (Blueprint $table): void {
            $table->unsignedTinyInteger('seat_number')->nullable()->after('stack');
        });
    }

    public function down(): void
    {
        Schema::table('poker_table_players', function (Blueprint $table): void {
            $table->dropColumn('seat_number');
        });
    }
};
