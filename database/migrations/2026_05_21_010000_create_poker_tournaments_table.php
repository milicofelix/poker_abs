<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poker_tournaments', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('status', 40)->default('registering');
            $table->unsignedInteger('buy_in')->default(1000);
            $table->unsignedInteger('starting_stack')->default(5000);
            $table->unsignedTinyInteger('max_players')->default(9);
            $table->unsignedTinyInteger('registered_players_count')->default(0);
            $table->unsignedInteger('prize_pool')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poker_tournaments');
    }
};
