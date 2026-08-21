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
        Schema::create('points_redemptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('redemption_type', ['cash', 'gift', 'discount']);
            $table->integer('points_used');
            $table->decimal('cash_amount', 10, 2)->nullable()->comment('Cash amount if cash withdrawal');
            $table->unsignedBigInteger('gift_id')->nullable()->comment('Gift ID if gift redemption');
            $table->enum('status', ['pending', 'approved', 'completed', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->string('payout_method')->nullable();
            $table->string('payout_reference')->nullable();
            $table->json('payout_details')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('refund_transaction_id')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            
            // Indexes for better performance
            $table->index('user_id');
            $table->index('status');
            $table->index('redemption_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_redemptions');
    }
};

