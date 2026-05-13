<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poker_players', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('poker_table_id')->constrained('poker_tables')->cascadeOnDelete();
            $table->unsignedTinyInteger('seat');
            $table->string('name');
            $table->string('type')->default('local_user');
            $table->unsignedInteger('stack')->default(1000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['poker_table_id', 'seat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poker_players');
    }
};
