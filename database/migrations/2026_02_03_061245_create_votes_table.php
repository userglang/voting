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
        Schema::create('votes', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('UUID identifier for the votes');

            $table->integer('control_number')->unsigned()->nullable()->comment('Control number; can be used to track voting order or count');

            $table->string('branch_number')->index()->comment('Reference to the associated branch');

            // Change member_code column type to string (matching the members table)
            $table->string('member_code')->nullable()->index()
                ->comment('Member Code reference; nullable if the vote is not linked to a member');

            $table->uuid('candidate_id')->nullable()->index()
                ->comment('Candidate ID reference; nullable if the vote is not linked to a candidate');

            $table->boolean('online_vote')->comment('Indicates if the vote was cast online');

            $table->timestamps();

            // Foreign key relationships
            $table->foreign('branch_number')
                ->references('branch_number')
                ->on('branches')
                ->onDelete('cascade')
                ->comment('Cascade delete votes when the associated branch is deleted.');

            // Foreign key for member_code (now string, matching members.code type)
            $table->foreign('member_code')
                ->references('code')
                ->on('members')
                ->onDelete('set null') // Optionally set to null instead of cascade delete
                ->comment('Set member_code to null if the associated member is deleted.');

            $table->foreign('candidate_id')
                ->references('id')
                ->on('candidates')
                ->onDelete('set null') // Optionally set to null instead of cascade delete
                ->comment('Set candidate_id to null if the associated candidate is deleted.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
