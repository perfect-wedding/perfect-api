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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->morphs('transactable');
            $table->string('reference')->nullable();
            $table->string('method')->nullable();
            $table->boolean('restricted')->default(false);
            $table->decimal('amount')->default(0.00);
            $table->decimal('due')->default(0.00);
            $table->decimal('tax')->default(0.00);
            $table->decimal('discount')->default(0.00);
            $table->decimal('offer_charge')->default(0.00);
            $table->enum('status', ['pending', 'in-progress', 'delivered', 'completed'])->default('pending');
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
        Schema::dropIfExists('transactions');
    }
};
