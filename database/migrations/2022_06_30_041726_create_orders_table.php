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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->morphs('company');
            $table->morphs('orderable');
            $table->string('code');
            $table->integer('qty')->default(1);
            $table->string('color')->nullable();
            $table->decimal('amount', 19, 4)->nullable(0.0);
            $table->string('destination');
            $table->boolean('accepted')->default(false);
            $table->enum('status', ['rejected', 'requesting', 'pending', 'in-progress', 'delivered', 'completed'])->default('requesting');
            $table->timestamp('due_date')->nullable();
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
        Schema::dropIfExists('orders');
    }
};
