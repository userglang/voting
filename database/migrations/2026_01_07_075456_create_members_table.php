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
        Schema::create('members', function (Blueprint $table) {
            // Primary Key
            $table->uuid('id')->primary()->comment('Primary UUID identifier for the member');

            // Basic Information
            $table->string('code')->nullable()->comment('System member code');
            $table->string('cid')->nullable()->comment('Custom Member Code or Customer ID');
            $table->string('branch_number')->comment('Reference to the associated branch');

            $table->string('first_name')->comment('First name of the member');
            $table->string('last_name')->comment('Last name of the member');
            $table->string('middle_name')->nullable()->comment('Middle name of the member');

            // Residential Address
            $table->string('address')->nullable()->comment('Full address of the member');
            $table->string('occupation')->nullable()->comment('Member current occupation');

            $table->date('birth_date')->nullable()->comment('Date of birth');
            $table->string('email')->nullable()->unique()->comment('Email address (must be unique if provided)');
            $table->string('contact_number')->nullable()->comment('Primary contact number');

            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable()->comment('Gender identity');
            $table->enum('marital_status', ['Single', 'Married', 'Separated', 'Widowed'])->nullable()->comment('Current marital status');
            $table->string('religion')->nullable()->comment('Member religion');

            $table->string('share_account')->nullable()->comment('Member share account number');
            $table->boolean('is_migs')->default(false)->comment('True if the member is MIGS');
            $table->string('share_amount')->nullable()->comment('Member share amount');

            // Status & Remarks
            $table->boolean('is_active')->default(true)->comment('True if the member is active');
            $table->boolean('is_registered')->default(true)->comment('Members application status');
            $table->enum('process_type', ['Updating Only', 'Updating and Voting'])->default('Updating and Voting')->comment('Registration process type');
            $table->enum('registration_type', ['Online', 'On-premise'])->default('Online')->comment('Registration type');

            $table->date('membership_date')->nullable()->comment('Date of membership');

            // Timestamps
            $table->timestamps();

            // Foreign Keys
            $table->foreign('branch_number')->references('branch_number')->on('branches')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
