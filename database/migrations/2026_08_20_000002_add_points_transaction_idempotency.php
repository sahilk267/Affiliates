<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('points_transactions') && !Schema::hasColumn('points_transactions', 'idempotency_key')) {
            Schema::table('points_transactions', function (Blueprint $table) {
                $table->string('idempotency_key')->nullable()->unique()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('points_transactions') && Schema::hasColumn('points_transactions', 'idempotency_key')) {
            Schema::table('points_transactions', function (Blueprint $table) {
                $table->dropUnique(['idempotency_key']);
                $table->dropColumn('idempotency_key');
            });
        }
    }
};
