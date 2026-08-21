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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id')->comment('User who created referral');
            $table->unsignedBigInteger('referred_id')->nullable()->comment('User who was referred (nullable until signup)');
            $table->string('referral_code', 50)->unique();
            $table->unsignedBigInteger('program_id')->nullable()->comment('Program this referral is for (nullable, for program-specific referrals)');
            $table->enum('status', ['pending', 'active', 'converted'])->default('pending');
            $table->timestamp('first_conversion_at')->nullable();
            $table->integer('total_points_earned')->default(0);
            $table->integer('total_conversions')->default(0);
            $table->timestamps();
            
            // Foreign key constraints
            
            // Indexes for better performance
            $table->index('referral_code');
            $table->index('referrer_id');
            $table->index('referred_id');
            $table->index('program_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};

