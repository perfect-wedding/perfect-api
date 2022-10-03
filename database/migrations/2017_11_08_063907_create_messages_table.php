<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lexx\ChatMessenger\Models\Models;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Models::table('threads'), function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('starred')->default(0);
            $table->string('subject');
            $table->string('type')->default('private');
            $table->json('data')->nullable();
            $table->string('slug')->nullable()->comment('Unique slug for social media sharing. MD5 hashed string');
            $table->integer('max_participants')->nullable()->comment('Max number of participants allowed');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->string('avatar')->nullable()->comment('Profile picture for the conversation');
            $table->timestamps();
        });

        Schema::create(Models::table('messages'), function (Blueprint $table) {
            $table->increments('id');
            $table->integer('thread_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('type')->default('text');
            $table->timestamps();
        });

        Schema::create(Models::table('participants'), function (Blueprint $table) {
            $table->increments('id');
            $table->integer('thread_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->timestamp('last_read')->nullable();
            $table->timestamps();
        });

        Schema::table(Models::table('participants'), function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table(Models::table('threads'), function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table(Models::table('messages'), function (Blueprint $table) {
            $table->softDeletes();
        });

        if (Schema::hasColumn(Models::table('threads'), 'starred')) {
            Schema::table(Models::table('threads'), function (Blueprint $table) {
                $table->dropColumn('starred');
            });
        }

        if (! Schema::hasColumn(Models::table('participants'), 'starred')) {
            Schema::table(Models::table('participants'), function (Blueprint $table) {
                $table->boolean('starred')->default(false)->after('last_read');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Models::table('participants'), function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table(Models::table('threads'), function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table(Models::table('messages'), function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        if (! Schema::hasColumn(Models::table('threads'), 'starred')) {
            Schema::table(Models::table('threads'), function (Blueprint $table) {
                $table->boolean('starred')->default(false)->after('id');
            });
        }
        if (Schema::hasColumn(Models::table('participants'), 'starred')) {
            Schema::table(Models::table('participants'), function (Blueprint $table) {
                $table->dropColumn('starred');
            });
        }
        Schema::dropIfExists(Models::table('threads'));
        Schema::dropIfExists(Models::table('messages'));
        Schema::dropIfExists(Models::table('participants'));
    }
}
