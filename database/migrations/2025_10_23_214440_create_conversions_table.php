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
        Schema::create('conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('click_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('user_id'); // The affiliate who gets the commission
            $table->string('conversion_id')->unique(); // External conversion ID from merchant
            $table->enum('event_type', ['purchase', 'signup', 'install', 'download', 'other'])->default('purchase');
            $table->json('event_data'); // Conversion details (order value, products, etc.)
            $table->decimal('order_value', 10, 2)->nullable(); // Order/sale value
            $table->string('currency', 3)->default('INR');
            $table->decimal('commission_amount', 10, 2); // Calculated commission
            $table->decimal('commission_rate', 5, 2); // Commission percentage
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->text('notes')->nullable(); // Admin notes
            $table->timestamp('converted_at'); // When the conversion happened
            $table->timestamp('approved_at')->nullable(); // When conversion was approved
            $table->unsignedBigInteger('approved_by')->nullable(); // Admin who approved
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('click_id')->references('id')->on('clicks')->onDelete('cascade');
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for better performance
            $table->index('user_id');
            $table->index('program_id');
            $table->index('status');
            $table->index('converted_at');
            $table->index('conversion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};
