<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poker_table_players', function (Blueprint $table): void {
            $table->boolean('is_bot')->default(false)->after('user_id');
            $table->string('bot_profile')->nullable()->after('is_bot');
            $table->string('bot_difficulty')->nullable()->after('bot_profile');
        });
    }

    public function down(): void
    {
        Schema::table('poker_table_players', function (Blueprint $table): void {
            $table->dropColumn(['is_bot', 'bot_profile', 'bot_difficulty']);
        });
    }
};
