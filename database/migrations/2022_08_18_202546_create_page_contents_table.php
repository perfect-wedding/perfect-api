<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
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
        Schema::create('homepage_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homepage_id')->constrained('homepages')->onUpdate('cascade')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->string('leading')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('content')->fullText()->nullable();
            $table->string('slug')->nullable();
            $table->string('image')->nullable();
            $table->string('image2')->nullable();
            $table->string('parent')->nullable();
            $table->boolean('linked')->default(false);
            $table->boolean('iterable')->default(false);
            $table->json('attached')->nullable();
            $table->string('template')->default('HomeContainer');
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
        Schema::dropIfExists('homepage_contents');
    }
};