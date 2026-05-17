<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poker_tables', function (Blueprint $table): void {
            $table->boolean('is_private')->default(false)->after('max_players');
            $table->string('invite_code', 12)->nullable()->unique()->after('is_private');
        });
    }

    public function down(): void
    {
        Schema::table('poker_tables', function (Blueprint $table): void {
            $table->dropUnique(['invite_code']);
            $table->dropColumn(['is_private', 'invite_code']);
        });
    }
};
