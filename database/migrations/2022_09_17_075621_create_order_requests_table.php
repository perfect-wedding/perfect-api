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
        Schema::create('order_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained('companies')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('package_id')->nullable();
            $table->morphs('orderable');
            $table->string('code');
            $table->integer('qty')->default(1);
            $table->decimal('amount', 19, 4)->nullable(0.0);
            $table->json('location')->nullable();
            $table->string('destination')->nullable();
            $table->boolean('accepted')->default(false);
            $table->boolean('rejected')->default(false);
            $table->string('reason')->nullable();
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
        Schema::dropIfExists('order_requests');
    }
};
