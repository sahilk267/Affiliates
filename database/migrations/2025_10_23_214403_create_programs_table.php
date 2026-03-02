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
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['ecommerce', 'finance', 'referral', 'app_download', 'other'])->default('ecommerce');
            $table->text('description')->nullable();
            $table->string('merchant_name');
            $table->string('merchant_url');
            $table->string('logo_url')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->json('commission_structure'); // Flexible commission structure
            $table->boolean('supports_sub_affiliate')->default(false); // For non-ecommerce programs
            $table->string('api_endpoint')->nullable();
            $table->json('api_credentials')->nullable(); // Encrypted API credentials
            $table->json('tracking_parameters')->nullable(); // Custom tracking parameters
            $table->integer('cookie_duration')->default(30); // Days
            $table->decimal('min_payout', 10, 2)->default(100.00);
            $table->enum('payout_frequency', ['weekly', 'monthly', 'quarterly'])->default('monthly');
            $table->json('restrictions')->nullable(); // Geographic or other restrictions
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('type');
            $table->index('status');
            $table->index('supports_sub_affiliate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
