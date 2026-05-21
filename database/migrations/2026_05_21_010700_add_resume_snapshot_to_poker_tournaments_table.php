<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poker_tournaments', function (Blueprint $table): void {
            if (! Schema::hasColumn('poker_tournaments', 'resume_token')) {
                $table->string('resume_token', 64)->nullable()->after('addon_available_until_blind_level');
            }

            if (! Schema::hasColumn('poker_tournaments', 'resume_snapshot')) {
                $table->json('resume_snapshot')->nullable()->after('resume_token');
            }

            if (! Schema::hasColumn('poker_tournaments', 'last_snapshot_at')) {
                $table->timestamp('last_snapshot_at')->nullable()->after('resume_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('poker_tournaments', function (Blueprint $table): void {
            foreach (['last_snapshot_at', 'resume_snapshot', 'resume_token'] as $column) {
                if (Schema::hasColumn('poker_tournaments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
