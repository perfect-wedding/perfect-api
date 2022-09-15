<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('verifications', function (Blueprint $table) {
            $table->json('data')->after('concierge_id')->nullable();
            $table->string('doc_ownerid')->after('real_address')->nullable();
            $table->string('doc_owner')->after('doc_ownerid')->nullable();
            $table->string('doc_inventory')->after('doc_owner')->nullable();
            $table->string('doc_invoice')->after('doc_inventory')->nullable();
            $table->string('doc_cac')->after('doc_invoice')->nullable();
            $table->json('rejected_docs')->after('doc_cac')->nullable();
            $table->string('reason')->after('rejected_docs')->nullable();
        });

        if (Schema::hasColumn('verifications', 'status'))
        {
            $sts = implode("', '", ['pending','verifying','verified', 'unverified', 'rejected']);
            DB::statement("ALTER TABLE `verifications` CHANGE `status` `status` ENUM('$sts') NOT NULL DEFAULT 'unverified';");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('verifications', function (Blueprint $table) {
            $table->dropColumn('data');
            $table->dropColumn('doc_ownerid');
            $table->dropColumn('doc_owner');
            $table->dropColumn('doc_inventory');
            $table->dropColumn('doc_invoice');
            $table->dropColumn('doc_cac');
            $table->dropColumn('reason');
        });
    }
};