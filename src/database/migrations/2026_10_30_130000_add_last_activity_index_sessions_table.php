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
        // Session clean-up deletes by last_activity; without an index it scans the whole table,
        // which bots fill with a new session on almost every visit
        Schema::table('sessions', function (Blueprint $table) {
            $table->index('last_activity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex(['last_activity']);
        });
    }
};
