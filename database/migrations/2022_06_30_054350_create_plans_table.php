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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('slug')->unique();
            $table->text('basic_info')->nullable();
            $table->text('extra_info')->nullable();
            $table->json('features')->nullable();
            $table->integer('duration')->default(30);
            $table->string('tenure')->default('month');
            $table->decimal('price')->default(0.00);
            $table->string('icon')->nullable();
            $table->string('cover', 550)->nullable();
            $table->boolean('trial')->default(false);
            $table->boolean('status')->default(true);
            $table->boolean('popular')->default(false);
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
        Schema::dropIfExists('plans');
    }
};
