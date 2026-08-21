<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function addColumnIfMissing(string $table, string $column, callable $definition): void
    {
        if (Schema::hasTable($table) && !Schema::hasColumn($table, $column)) {
            Schema::table($table, $definition);
        }
    }

    private function foreignKeyExists(string $table, string $column, string $referencedTable): bool
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            return DB::table('information_schema.KEY_COLUMN_USAGE')
                ->whereRaw('TABLE_SCHEMA = DATABASE()')
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->where('REFERENCED_TABLE_NAME', $referencedTable)
                ->exists();
        }

        if ($driver === 'sqlite') {
            foreach (DB::select('PRAGMA foreign_key_list("' . $table . '")') as $foreignKey) {
                if (($foreignKey->from ?? null) === $column && ($foreignKey->table ?? null) === $referencedTable) {
                    return true;
                }
            }
        }

        return false;
    }

    private function addForeignKeyIfMissing(string $table, string $column, string $referencedTable, string $onDelete = 'cascade'): void
    {
        if (Schema::hasTable($table) && Schema::hasTable($referencedTable) && !$this->foreignKeyExists($table, $column, $referencedTable)) {
            Schema::table($table, function (Blueprint $schema) use ($column, $referencedTable, $onDelete) {
                $foreign = $schema->foreign($column)->references('id')->on($referencedTable);
                $onDelete === 'set null' ? $foreign->nullOnDelete() : $foreign->cascadeOnDelete();
            });
        }
    }

    public function up(): void
    {
        $this->addColumnIfMissing('conversions', 'link_id', fn (Blueprint $table) => $table->unsignedBigInteger('link_id')->nullable()->after('click_id'));
        $this->addColumnIfMissing('conversions', 'partner_event_id', fn (Blueprint $table) => $table->string('partner_event_id')->nullable()->unique()->after('conversion_id'));
        $this->addColumnIfMissing('conversions', 'conversion_value', fn (Blueprint $table) => $table->decimal('conversion_value', 10, 2)->nullable()->after('event_data'));
        $this->addColumnIfMissing('conversions', 'order_id', fn (Blueprint $table) => $table->string('order_id')->nullable());
        $this->addColumnIfMissing('conversions', 'customer_id', fn (Blueprint $table) => $table->string('customer_id')->nullable());
        $this->addColumnIfMissing('conversions', 'product_id', fn (Blueprint $table) => $table->string('product_id')->nullable());
        $this->addColumnIfMissing('conversions', 'product_name', fn (Blueprint $table) => $table->string('product_name')->nullable());
        $this->addColumnIfMissing('conversions', 'quantity', fn (Blueprint $table) => $table->unsignedInteger('quantity')->nullable());
        $this->addColumnIfMissing('conversions', 'sub_affiliate_id', fn (Blueprint $table) => $table->unsignedBigInteger('sub_affiliate_id')->nullable());
        $this->addColumnIfMissing('conversions', 'sub_affiliate_commission', fn (Blueprint $table) => $table->decimal('sub_affiliate_commission', 10, 2)->default(0));
        $this->addColumnIfMissing('conversions', 'processed_at', fn (Blueprint $table) => $table->timestamp('processed_at')->nullable());

        $this->addColumnIfMissing('commissions', 'commission_type', fn (Blueprint $table) => $table->string('commission_type')->default('affiliate'));
        $this->addColumnIfMissing('commissions', 'payout_method', fn (Blueprint $table) => $table->string('payout_method')->nullable());
        $this->addColumnIfMissing('commissions', 'payout_details', fn (Blueprint $table) => $table->json('payout_details')->nullable());

        $this->addForeignKeyIfMissing('product_links', 'product_id', 'products');
        $this->addForeignKeyIfMissing('product_links', 'program_id', 'programs');
        $this->addForeignKeyIfMissing('product_links', 'link_id', 'links');
        $this->addForeignKeyIfMissing('product_commissions', 'product_id', 'products');
        $this->addForeignKeyIfMissing('product_commissions', 'program_id', 'programs');
        $this->addForeignKeyIfMissing('user_points', 'user_id', 'users');
        $this->addForeignKeyIfMissing('points_transactions', 'user_id', 'users');
        $this->addForeignKeyIfMissing('referrals', 'referrer_id', 'users');
        $this->addForeignKeyIfMissing('referrals', 'referred_id', 'users', 'set null');
        $this->addForeignKeyIfMissing('referrals', 'program_id', 'programs', 'set null');
        $this->addForeignKeyIfMissing('cashback_settings', 'program_id', 'programs');
        $this->addForeignKeyIfMissing('points_redemptions', 'user_id', 'users');
        $this->addForeignKeyIfMissing('points_redemptions', 'gift_id', 'gifts', 'set null');
        $this->addForeignKeyIfMissing('conversions', 'click_id', 'clicks');
        $this->addForeignKeyIfMissing('conversions', 'link_id', 'links', 'set null');
        $this->addForeignKeyIfMissing('conversions', 'program_id', 'programs');
        $this->addForeignKeyIfMissing('conversions', 'user_id', 'users');
        $this->addForeignKeyIfMissing('conversions', 'sub_affiliate_id', 'users', 'set null');
        $this->addForeignKeyIfMissing('conversions', 'approved_by', 'users', 'set null');
        $this->addForeignKeyIfMissing('commissions', 'conversion_id', 'conversions');
        $this->addForeignKeyIfMissing('commissions', 'user_id', 'users');
        $this->addForeignKeyIfMissing('commissions', 'parent_user_id', 'users', 'set null');

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE conversions MODIFY event_type ENUM('purchase','signup','download','install','lead','click','other') NOT NULL DEFAULT 'purchase'");
        }
    }

    public function down(): void
    {
        // The compatibility migration is intentionally non-destructive. Financial data must be rolled back with an explicit, reviewed migration.
    }
};
