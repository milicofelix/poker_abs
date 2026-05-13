<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poker_table_seats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('poker_table_id')->constrained('poker_tables')->cascadeOnDelete();
            $table->foreignId('poker_player_id')->nullable()->constrained('poker_players')->nullOnDelete();
            $table->unsignedTinyInteger('seat_number');
            $table->string('status')->default('empty');
            $table->string('role')->nullable();
            $table->unsignedInteger('stack_snapshot')->default(0);
            $table->boolean('is_dealer')->default(false);
            $table->boolean('is_small_blind')->default(false);
            $table->boolean('is_big_blind')->default(false);
            $table->timestamps();

            $table->unique(['poker_table_id', 'seat_number']);
            $table->index(['poker_table_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poker_table_seats');
    }
};
