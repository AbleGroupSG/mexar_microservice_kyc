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
        Schema::table('webhook_logs', function (Blueprint $table) {
            $table->string('idempotency_key', 128)
                ->nullable()
                ->unique('uniq_webhook_logs_idempotency_key')
                ->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            if (Schema::hasColumn('webhook_logs', 'idempotency_key')) {
                $table->dropUnique('uniq_webhook_logs_idempotency_key');
                $table->dropColumn('idempotency_key');
            }
        });
    }
};
