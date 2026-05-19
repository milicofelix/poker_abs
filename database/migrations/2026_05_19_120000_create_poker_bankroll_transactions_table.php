<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poker_bankroll_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poker_table_id')->nullable()->constrained('poker_tables')->nullOnDelete();
            $table->foreignId('poker_hand_id')->nullable()->constrained('poker_hands')->nullOnDelete();
            $table->foreignId('poker_table_player_id')->nullable()->constrained('poker_table_players')->nullOnDelete();
            $table->string('type', 40);
            $table->integer('amount');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['poker_table_id', 'poker_hand_id']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poker_bankroll_transactions');
    }
};
