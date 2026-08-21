<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversion_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_user_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('parent_amount', 10, 2)->default(0);
            $table->decimal('sub_affiliate_amount', 10, 2)->default(0);
            $table->string('commission_type')->default('affiliate');
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->string('currency', 3)->default('INR');
            $table->string('payment_method')->nullable();
            $table->string('payout_method')->nullable();
            $table->json('payout_details')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('paid_at');
            $table->index('conversion_id');
            $table->index('commission_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
