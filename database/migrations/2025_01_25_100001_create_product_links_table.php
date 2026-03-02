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
        Schema::create('product_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('link_id');
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('availability')->nullable();
            $table->boolean('is_best_price')->default(false);
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
            $table->foreign('link_id')->references('id')->on('links')->onDelete('cascade');
            
            // Indexes for better performance
            $table->index('product_id');
            $table->index('program_id');
            $table->index('is_best_price');
            $table->index('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_links');
    }
};

