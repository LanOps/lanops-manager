<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('private_key', 64)->nullable()->unique()->after('status');
        });

        // Events that are already Private get their secret link straight away
        DB::table('events')->where('status', 'PRIVATE')->whereNull('private_key')->orderBy('id')
            ->chunkById(100, function ($events) {
                foreach ($events as $event) {
                    DB::table('events')->where('id', $event->id)->update(['private_key' => Str::random(32)]);
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique(['private_key']);
            $table->dropColumn('private_key');
        });
    }
};
