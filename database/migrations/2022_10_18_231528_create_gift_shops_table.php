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
        Schema::create('gift_shops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->string('image')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('socials')->nullable();
            $table->string('merchant_name')->nullable();
            $table->boolean('active')->default(false);
            $table->string('invite_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gift_shops');
    }
};
