<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_link_id')
                ->constrained('product_links')
                ->cascadeOnDelete();
            $table->string('source', 100);
            $table->string('external_offer_id', 191)->nullable();
            $table->timestamp('observed_at');
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('availability', 50)->nullable();
            $table->decimal('rating', 4, 2)->nullable();
            $table->unsignedInteger('rating_count')->nullable();
            $table->decimal('original_price', 12, 2)->nullable();
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['product_link_id', 'observed_at']);
            $table->index(['source', 'external_offer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_snapshots');
    }
};
