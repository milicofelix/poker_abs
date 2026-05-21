<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poker_tournaments', function (Blueprint $table): void {
            $table->foreignId('poker_table_id')
                ->nullable()
                ->after('id')
                ->constrained('poker_tables')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('poker_tournaments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('poker_table_id');
        });
    }
};
