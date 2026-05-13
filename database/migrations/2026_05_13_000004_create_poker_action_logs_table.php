<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poker_action_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('poker_hand_id')->constrained('poker_hands')->cascadeOnDelete();
            $table->foreignId('poker_player_id')->nullable()->constrained('poker_players')->nullOnDelete();
            $table->string('street');
            $table->string('action');
            $table->unsignedInteger('amount')->default(0);
            $table->unsignedInteger('pot_after_action')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('acted_at')->nullable();

            $table->index(['poker_hand_id', 'street']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poker_action_logs');
    }
};
