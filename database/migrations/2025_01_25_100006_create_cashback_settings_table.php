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
        Schema::create('cashback_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id')->unique();
            $table->decimal('cashback_rate', 5, 2)->comment('Cashback percentage e.g., 20.00 = 20%');
            $table->decimal('referral_rate', 5, 2)->default(0)->comment('Referral commission percentage (for non-e-commerce)');
            $table->decimal('min_purchase_amount', 10, 2)->default(0);
            $table->decimal('max_cashback_amount', 10, 2)->nullable();
            $table->integer('points_per_rupee')->default(1)->comment('Conversion rate: 1 rupee = X points');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            
            // Foreign key constraints
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashback_settings');
    }
};

