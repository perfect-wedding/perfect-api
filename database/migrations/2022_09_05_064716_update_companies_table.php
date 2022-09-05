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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('rc_number')->after('city')->nullable();
            $table->string('rc_company_type')->after('rc_number')->nullable();
        });

        if (Schema::hasColumn('companies', 'status'))
        {
            $sts = implode("', '", ['unverified', 'pending', 'verifying', 'verified']);
            DB::statement("ALTER TABLE `companies` CHANGE `status` `status` ENUM('$sts') NOT NULL DEFAULT 'unverified';");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('rc_number');
            $table->dropColumn('rc_company_type');
        });
    }
};