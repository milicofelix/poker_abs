<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poker_tables', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('status')->default('waiting');
            $table->unsignedInteger('small_blind')->default(10);
            $table->unsignedInteger('big_blind')->default(20);
            $table->unsignedTinyInteger('max_players')->default(2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poker_tables');
    }
};
