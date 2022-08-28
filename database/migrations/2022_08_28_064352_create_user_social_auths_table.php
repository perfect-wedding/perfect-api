<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_social_auths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('email')->index()->nullable();
            $table->string('github_id')->nullable();
            $table->string('github_token')->nullable();
            $table->string('github_refresh_token')->nullable();
            $table->timestamp('github_expires_at')->nullable();
            $table->string('google_id')->nullable();
            $table->string('google_token')->nullable();
            $table->string('google_refresh_token')->nullable();
            $table->timestamp('google_expires_at')->nullable();
            $table->string('facebook_id')->nullable();
            $table->string('facebook_token')->nullable();
            $table->string('facebook_refresh_token')->nullable();
            $table->timestamp('facebook_expires_at')->nullable();
            $table->string('twitter_id')->nullable();
            $table->string('twitter_token')->nullable();
            $table->string('twitter_refresh_token')->nullable();
            $table->timestamp('twitter_expires_at')->nullable();
            $table->timestamps();
        });

        if (!Schema::hasColumn('personal_access_tokens', 'expires_at'))
        {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->timestamp('expires_at')->after('last_used_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_social_auths');

        if (Schema::hasColumn('personal_access_tokens', 'expires_at'))
        {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->dropColumn('expires_at');
            });
        }
    }
};