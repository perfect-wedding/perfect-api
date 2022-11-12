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
<<<<<<< HEAD
            $table->nullableMorphs('company');
            $table->nullableMorphs('eventable');
            $table->boolean('notify')->default(false);
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('details')->nullable();
            $table->text('location')->nullable();
=======
            $table->foreignId('company_id')->constrained('companies')->onUpdate('cascade')->onDelete('cascade');
            $table->nullableMorphs('eventable');
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('details')->nullable();
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('bgcolor')->nullable();
            $table->string('border_color')->nullable();
            $table->json('meta')->nullable();
            $table->integer('duration')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
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
<<<<<<< HEAD
        Schema::dropIfExists('events');
=======
        Schema::dropIfExists('calendars');
>>>>>>> 74877e1d6e74e818d4c6bd2d6d77ae7b1bd4ac0d
    }
};
