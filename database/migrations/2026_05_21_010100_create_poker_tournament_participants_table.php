<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poker_tournament_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('poker_tournament_id')->constrained('poker_tournaments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 40)->default('registered');
            $table->unsignedInteger('starting_stack')->default(5000);
            $table->unsignedInteger('current_stack')->default(5000);
            $table->unsignedTinyInteger('finish_position')->nullable();
            $table->unsignedInteger('prize_amount')->default(0);
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('eliminated_at')->nullable();
            $table->timestamps();

            $table->unique(['poker_tournament_id', 'user_id'], 'poker_tournament_user_unique');
            $table->index(['user_id', 'status']);
            $table->index(['poker_tournament_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poker_tournament_participants');
    }
};
