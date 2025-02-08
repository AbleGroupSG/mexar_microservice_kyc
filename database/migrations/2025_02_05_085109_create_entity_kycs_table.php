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
        Schema::create('kyc_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->json('profile_data')->nullable();

            $table->string('provider', 32)->index('idx_kyc_profiles_provider');
            $table->string('status', 32)->nullable()->index('idx_kyc_profiles_status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_profiles');
    }
};
