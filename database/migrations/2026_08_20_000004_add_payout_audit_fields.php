<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function addColumnIfMissing(string $table, string $column, callable $definition): void
    {
        if (Schema::hasTable($table) && !Schema::hasColumn($table, $column)) {
            Schema::table($table, $definition);
        }
    }

    public function up(): void
    {
        foreach ([
            'approved_at' => fn (Blueprint $table) => $table->timestamp('approved_at')->nullable(),
            'approved_by' => fn (Blueprint $table) => $table->unsignedBigInteger('approved_by')->nullable(),
            'cancelled_at' => fn (Blueprint $table) => $table->timestamp('cancelled_at')->nullable(),
            'cancelled_by' => fn (Blueprint $table) => $table->unsignedBigInteger('cancelled_by')->nullable(),
            'paid_by' => fn (Blueprint $table) => $table->unsignedBigInteger('paid_by')->nullable(),
        ] as $column => $definition) {
            $this->addColumnIfMissing('commissions', $column, $definition);
        }

        foreach ([
            'payout_method' => fn (Blueprint $table) => $table->string('payout_method')->nullable(),
            'payout_reference' => fn (Blueprint $table) => $table->string('payout_reference')->nullable(),
            'payout_details' => fn (Blueprint $table) => $table->json('payout_details')->nullable(),
            'approved_by' => fn (Blueprint $table) => $table->unsignedBigInteger('approved_by')->nullable(),
            'approved_at' => fn (Blueprint $table) => $table->timestamp('approved_at')->nullable(),
            'rejected_by' => fn (Blueprint $table) => $table->unsignedBigInteger('rejected_by')->nullable(),
            'rejected_at' => fn (Blueprint $table) => $table->timestamp('rejected_at')->nullable(),
            'completed_by' => fn (Blueprint $table) => $table->unsignedBigInteger('completed_by')->nullable(),
            'completed_at' => fn (Blueprint $table) => $table->timestamp('completed_at')->nullable(),
            'refund_transaction_id' => fn (Blueprint $table) => $table->unsignedBigInteger('refund_transaction_id')->nullable(),
            'idempotency_key' => fn (Blueprint $table) => $table->string('idempotency_key')->nullable()->unique(),
        ] as $column => $definition) {
            $this->addColumnIfMissing('points_redemptions', $column, $definition);
        }
    }

    public function down(): void
    {
        // Payout audit history is not removed automatically. Use an explicit reviewed migration for rollback.
    }
};
