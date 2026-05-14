<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poker_hands', function (Blueprint $table): void {
            $table->json('state_payload')->nullable()->after('dealer_position');
        });
    }

    public function down(): void
    {
        Schema::table('poker_hands', function (Blueprint $table): void {
            $table->dropColumn('state_payload');
        });
    }
};
