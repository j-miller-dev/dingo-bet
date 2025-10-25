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
        Schema::create('odds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Home", "Away", "Draw", "Over 2.5", "Under 2.5"
            $table->decimal('value', 5, 2); // e.g., 1.85, 2.30, 3.50
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['market_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odds');
    }
};
