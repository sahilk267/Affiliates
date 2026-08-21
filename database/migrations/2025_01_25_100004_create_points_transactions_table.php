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
        Schema::create('points_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['credit', 'debit']);
            $table->integer('points');
            $table->integer('balance_after')->comment('Balance after this transaction');
            $table->string('description');
            $table->string('reference_type')->comment('Type: purchase_cashback, referral, redemption, gift, bonus, adjustment');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('ID of related record');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();
            
            // Foreign key constraints
            
            // Indexes for better performance
            $table->index('user_id');
            $table->index('type');
            $table->index('reference_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_transactions');
    }
};

