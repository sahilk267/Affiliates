<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('click_id');
            $table->unsignedBigInteger('link_id')->nullable();
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('sub_affiliate_id')->nullable();
            $table->string('conversion_id')->nullable()->unique();
            $table->string('partner_event_id')->nullable()->unique();
            $table->enum('event_type', ['purchase', 'signup', 'download', 'install', 'lead', 'click', 'other'])->default('purchase');
            $table->json('event_data')->nullable();
            $table->decimal('conversion_value', 10, 2)->nullable();
            $table->decimal('order_value', 10, 2)->nullable();
            $table->string('currency', 3)->default('INR');
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->decimal('sub_affiliate_commission', 10, 2)->default(0);
            $table->string('order_id')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('program_id');
            $table->index('status');
            $table->index('converted_at');
            $table->index('partner_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};
