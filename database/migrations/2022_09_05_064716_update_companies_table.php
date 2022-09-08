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
        if (Schema::hasColumn('companies', 'status'))
        {
            $sts = implode("', '", ['unverified', 'pending', 'verifying', 'verified']);
            DB::statement("ALTER TABLE `companies` CHANGE `status` `status` ENUM('$sts') NOT NULL DEFAULT 'unverified';");
            $tps = implode("', '", ['vendor', 'provider']);
            DB::statement("ALTER TABLE `companies` CHANGE `type` `type` ENUM('$tps') NOT NULL DEFAULT 'provider';");
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->json('verified_data')->after('city')->nullable();
            $table->string('rc_number')->after('verified_data')->nullable();
            $table->string('rc_company_type')->after('rc_number')->nullable();
            $table->enum('role', ['individual', 'company'])->after('type')->default('individual');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('verified_data');
            $table->dropColumn('rc_number');
            $table->dropColumn('rc_company_type');
            $table->dropColumn('role');
        });
    }
};