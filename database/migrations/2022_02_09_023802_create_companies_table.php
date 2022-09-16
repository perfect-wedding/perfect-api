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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('slug');
            $table->string('name')->index();
            $table->enum('type', ['vendor', 'provider'])->default('provider');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('intro')->nullable();
            $table->text('about')->nullable();
            $table->string('postal')->nullable();
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('banner')->nullable();
            $table->string('logo')->nullable();
            $table->enum('status', ['pending', 'verifying', 'verified'])->default('pending');
            $table->dateTime('featured_to')->nullable();
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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('companies');
    }
};
