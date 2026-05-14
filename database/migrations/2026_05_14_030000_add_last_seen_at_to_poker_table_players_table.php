<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poker_table_players', function (Blueprint $table): void {
            $table->timestamp('last_seen_at')->nullable()->after('left_at');
        });
    }

    public function down(): void
    {
        Schema::table('poker_table_players', function (Blueprint $table): void {
            $table->dropColumn('last_seen_at');
        });
    }
};
