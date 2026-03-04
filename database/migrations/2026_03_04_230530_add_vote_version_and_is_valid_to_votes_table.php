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
        Schema::table('votes', function (Blueprint $table) {
            $table->unsignedInteger('vote_version')
                ->default(1)
                ->after('id')
                ->comment('Version of the vote (used for revotes)');

            $table->boolean('is_valid')
                ->default(true)
                ->after('vote_version')
                ->comment('Indicates if the vote is valid or invalidated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropColumn(['vote_version', 'is_valid']);
        });
    }
};
