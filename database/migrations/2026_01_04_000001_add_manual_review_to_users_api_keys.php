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
        Schema::table('users_api_keys', function (Blueprint $table) {
            $table->boolean('need_manual_review')->default(false)->after('webhook_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_api_keys', function (Blueprint $table) {
            $table->dropColumn('need_manual_review');
        });
    }
};
