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
        Schema::create('portfolio_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->index();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->morphs('portfoliable');
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('layout')->nullable();
            $table->boolean('active')->default(1);
            $table->boolean('edge')->default(0);
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
        Schema::dropIfExists('portfolio_pages');
    }
};
