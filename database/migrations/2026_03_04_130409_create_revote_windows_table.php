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
        Schema::create('revote_windows', function (Blueprint $table) {
            $table->uuid('id')
                ->primary()
                ->comment('UUID identifier for the revote window');

            $table->uuid('position_id')
                ->nullable()
                ->comment('Position of the candidate');

            $table->text('reason')
                ->nullable()
                ->comment('Reason for opening the revote window');

            $table->timestamp('start_at')
                ->nullable()
                ->comment('Revote start date and time');

            $table->timestamp('end_at')
                ->nullable()
                ->comment('Revote end date and time');

            $table->timestamps();

            $table->foreign('position_id')
                ->references('id')
                ->on('positions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revote_windows');
    }
};
