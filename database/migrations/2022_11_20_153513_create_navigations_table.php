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
        Schema::create('navigations', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->morphs('navigable');
            $table->string('tree')->nullable();
            $table->string('location')->nullable();
            $table->string('group')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('active')->default(true);
            $table->boolean('important')->default(true);
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
        Schema::dropIfExists('navigations');
    }
};
