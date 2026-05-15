<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poker_bot_decision_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('poker_table_id')->constrained('poker_tables')->cascadeOnDelete();
            $table->foreignId('poker_table_player_id')->constrained('poker_table_players')->cascadeOnDelete();
            $table->string('actor', 20);
            $table->string('profile', 30);
            $table->string('difficulty', 20);
            $table->string('street', 20);
            $table->string('action', 20);
            $table->unsignedInteger('amount')->default(0);
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('range')->nullable();
            $table->string('label')->nullable();
            $table->string('board_texture')->nullable();
            $table->boolean('has_flush_draw')->default(false);
            $table->boolean('has_straight_draw')->default(false);
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['poker_table_player_id', 'created_at']);
            $table->index(['poker_table_id', 'street']);
            $table->index(['profile', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poker_bot_decision_logs');
    }
};
