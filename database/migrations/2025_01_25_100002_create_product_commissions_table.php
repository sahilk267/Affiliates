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
        Schema::create('product_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('program_id');
            $table->decimal('commission_rate', 5, 2)->comment('Percentage e.g., 2.50 = 2.5%');
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('fixed_amount', 10, 2)->nullable()->comment('Fixed commission if type is fixed');
            $table->string('category')->nullable()->comment('Product category for category-wise rates');
            $table->decimal('min_purchase', 10, 2)->default(0)->comment('Minimum purchase amount for commission');
            $table->decimal('max_commission', 10, 2)->nullable()->comment('Maximum commission per transaction');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('source', ['manual', 'api', 'import'])->default('manual')->comment('How commission was added');
            $table->timestamp('last_updated_at')->nullable()->comment('When commission rate was last updated');
            $table->timestamps();
            
            // Foreign key constraints
            
            // Unique constraint: one commission rate per product+program combination
            $table->unique(['product_id', 'program_id'], 'unique_product_program');
            
            // Indexes for better performance
            $table->index('commission_rate');
            $table->index('status');
            $table->index('category');
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_commissions');
    }
};

