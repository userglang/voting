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
        Schema::create('candidates', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('UUID identifier for the candidate');

            $table->uuid('position_id')
                ->nullable()
                ->comment('Position of the candidate');

            $table->string('first_name')->comment('First name of the candidate');
            $table->string('last_name')->comment('Last name of the candidate');
            $table->string('middle_name')->nullable()->comment('Middle name of the candidate');

            $table->string('background_profile')
                ->nullable()
                ->comment('Brief candidate profile background.');

            $table->string('image')
                ->nullable()
                ->comment('Candidate image name');

            $table->foreign('position_id')
                ->references('id')
                ->on('positions')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
