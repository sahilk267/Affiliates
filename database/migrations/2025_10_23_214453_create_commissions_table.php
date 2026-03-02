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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversion_id');
            $table->unsignedBigInteger('user_id'); // The affiliate receiving the commission
            $table->unsignedBigInteger('parent_user_id')->nullable(); // For sub-affiliate splits
            $table->decimal('amount', 10, 2); // Commission amount
            $table->decimal('parent_amount', 10, 2)->default(0.00); // Amount for parent affiliate
            $table->decimal('sub_affiliate_amount', 10, 2)->default(0.00); // Amount for sub-affiliate
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->string('currency', 3)->default('INR');
            $table->string('payment_method')->nullable(); // bank_transfer, upi, etc.
            $table->string('transaction_id')->nullable(); // Payment transaction ID
            $table->timestamp('paid_at')->nullable(); // When commission was paid
            $table->text('notes')->nullable(); // Payment notes
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('conversion_id')->references('id')->on('conversions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes for better performance
            $table->index('user_id');
            $table->index('status');
            $table->index('paid_at');
            $table->index('conversion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
