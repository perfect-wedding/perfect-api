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
        Schema::create('homepages', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->longText('details')->nullable()->fulltext();
            $table->string('banner')->nullable();
            $table->string('meta')->fullText()->nullable();
            $table->string('slug')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('default')->default(false);
            $table->boolean('landing')->default(false);
            $table->boolean('scrollable')->default(false);
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
        Schema::dropIfExists('homepages');
    }
};