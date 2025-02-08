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
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();            
            $table->string('provider', 32)->index('idx_api_request_logs_provider');
            $table->uuid('kyc_profile_id')->index('idx_api_request_logs_kyc_profile_id');
            $table->text('payload'); // request payload? 
            $table->text('response')->nullable();
            $table->timestamps();

            $table->foreign('kyc_profile_id')->references('id')->on('kyc_profiles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
