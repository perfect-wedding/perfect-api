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
        Schema::create('escrow_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->nullableMorphs('walletable');
            $table->decimal('amount', 19, 4)->default(0.00);
            $table->string('source')->nullable();
            $table->string('detail')->nullable();
            $table->enum('type', ['debit', 'withdrawal', 'credit'])->default('credit');
            $table->enum('status', ['held', 'released', 'declined', 'failed'])->default('held');
            $table->string('escaped')->default(false);
            $table->string('reference')->nullable();
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
        Schema::dropIfExists('wallets');
    }
};
