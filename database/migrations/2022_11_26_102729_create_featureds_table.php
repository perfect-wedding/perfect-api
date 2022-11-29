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
        Schema::create('featureds', function (Blueprint $table) {
            $table->id();
            $table->morphs('featureable');
            $table->foreignId('plan_id')->constrained('plans')->onUpdate('cascade')->onDelete('cascade');
            $table->json('meta')->nullable();
            $table->json('places')->nullable();
            $table->integer('duration')->default(1);
            $table->string('tenure')->default('daily');
            $table->boolean('active')->default(false);
            $table->boolean('pending')->default(false);
            $table->boolean('recurring')->default(false);
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
        Schema::dropIfExists('featureds');
    }
};
