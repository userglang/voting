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
        Schema::create('positions', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('UUID identifier for the position');

            // Title of the position
            $table->string('title')->unique()->comment('Title of the position');

            // Number of vacant positions
            $table->integer('vacant_count')->default(0)->comment('Number of vacant positions available for voting');

            // Priority number for the position (lower number = higher priority)
            $table->integer('priority')->default(0)->comment('Priority number for ordering positions (lower = higher priority)');

            // Indicates whether the position is active or inactive
            $table->boolean('is_active')->default(true)->comment('Indicates whether the position is active or inactive');

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
