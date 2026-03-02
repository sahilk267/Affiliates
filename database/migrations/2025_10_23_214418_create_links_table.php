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
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('user_id');
            $table->string('original_url'); // The original merchant URL
            $table->string('affiliate_url'); // The generated affiliate URL
            $table->string('short_code')->unique(); // Short code for the link
            $table->string('sub_id')->nullable(); // Custom sub-ID for tracking
            $table->string('campaign_name')->nullable(); // Campaign or promotion name
            $table->text('description')->nullable(); // Link description
            $table->json('tracking_parameters')->nullable(); // Additional tracking parameters
            $table->boolean('is_active')->default(true);
            $table->integer('click_count')->default(0);
            $table->integer('conversion_count')->default(0);
            $table->decimal('total_commission', 10, 2)->default(0.00);
            $table->timestamp('expires_at')->nullable(); // Link expiration
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes for better performance
            $table->index('user_id');
            $table->index('program_id');
            $table->index('is_active');
            $table->index('short_code');
            $table->index('sub_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
