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
            $table->uuid('id')->primary(); // 00000000-0000-0000-0000-000000000000

            $table->json('profile_data'); // json data

            $table->string('provider', 32)->index('idx_kyc_profiles_provider');
            $table->string('status', 32)->index('idx_kyc_profiles_status'); // approved, pending, rejected                    
            
            $table->timestamps();
        });



        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_kycs');
    }
};
