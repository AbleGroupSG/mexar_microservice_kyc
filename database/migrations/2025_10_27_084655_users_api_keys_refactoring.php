<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 128)->index('idx_users_api_keys_name');
            $table->string('api_key', 128)->unique('uniq_users_api_keys_api_key');
            $table->string('signature_key', 128)->nullable();
            $table->string('webhook_url', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('api_key');
        });

        Schema::table('kyc_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_api_key_id')
                ->nullable()
                ->index('idx_kyc_profiles_user_api_key_id')
                ->after('user_id')
                ->comment('user_api_key_id');

            $table->foreign('user_api_key_id', 'fk_kyc_profiles_user_api_key_id')
                ->references('id')
                ->on('users_api_keys');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kyc_profiles', function (Blueprint $table) {
            $table->dropForeign('fk_kyc_profiles_user_api_key_id');
            $table->dropIndex('idx_kyc_profiles_user_api_key_id');
            $table->dropColumn('user_api_key_id');
        });
        Schema::dropIfExists('users_api_keys');;
    }
};
