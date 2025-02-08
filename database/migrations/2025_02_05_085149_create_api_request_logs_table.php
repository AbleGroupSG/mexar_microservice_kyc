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
            $table->string('method', 16)->index('idx_api_request_logs_method');

            $table->text('payload')->index('idx_api_request_logs_payload');
            $table->text('response')->nullable()->index('idx_api_request_logs_response');
            $table->timestamps();
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
