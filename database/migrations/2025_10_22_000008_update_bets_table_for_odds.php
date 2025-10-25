<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            // Add odds fields
            $table->foreignId('odds_id')->nullable()->after('event_id')->constrained()->onDelete('cascade');
            $table->decimal('odds_value', 5, 2)->nullable()->after('selection');

            // Make selection nullable since we'll use odds_id instead
            $table->string('selection')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->dropForeign(['odds_id']);
            $table->dropColumn(['odds_id', 'odds_value']);
        });
    }
};
