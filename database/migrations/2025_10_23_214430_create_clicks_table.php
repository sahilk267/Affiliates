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
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('link_id');
            $table->unsignedBigInteger('user_id'); // The affiliate who owns the link
            $table->unsignedBigInteger('program_id');
            $table->string('ip_address');
            $table->text('user_agent');
            $table->string('referrer')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('device_type')->nullable(); // mobile, desktop, tablet
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->json('tracking_data')->nullable(); // Additional tracking information
            $table->boolean('is_unique')->default(true); // First click from this IP
            $table->boolean('is_converted')->default(false); // Whether this click led to conversion
            $table->timestamp('clicked_at');
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('link_id')->references('id')->on('links')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
            
            // Indexes for better performance
            $table->index('user_id');
            $table->index('program_id');
            $table->index('ip_address');
            $table->index('clicked_at');
            $table->index('is_converted');
            $table->index('is_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clicks');
    }
};
