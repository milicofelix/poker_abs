<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poker_hands', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('poker_table_id')->constrained('poker_tables')->cascadeOnDelete();
            $table->uuid('code')->unique();
            $table->string('status')->default('running');
            $table->string('street')->default('pre_flop');
            $table->unsignedInteger('pot')->default(0);
            $table->unsignedInteger('current_bet')->default(0);
            $table->unsignedTinyInteger('dealer_position')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['poker_table_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poker_hands');
    }
};
