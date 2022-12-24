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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade');
            $table->nullableMorphs('company');
            $table->nullableMorphs('eventable');
            $table->boolean('notify')->default(false);
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('details')->nullable();
            $table->text('location')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('bgcolor')->nullable();
            $table->string('border_color')->nullable();
            $table->json('meta')->nullable();
            $table->integer('duration')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->string('status')->nullable();
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
        Schema::dropIfExists('events');
    }
};
