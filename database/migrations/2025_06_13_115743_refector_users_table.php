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
        Schema::table('users', function (Blueprint $table) {
            $table->string('api_key', )->nullable();
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
        });

        Schema::table('kyc_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->after('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('api_key');
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });

        Schema::table('kyc_profiles', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
