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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'affiliate', 'sub_affiliate'])->default('affiliate');
            $table->unsignedBigInteger('parent_id')->nullable(); // For sub-affiliates
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->string('pan_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // User preferences and settings
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('role');
            $table->index('is_active');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
