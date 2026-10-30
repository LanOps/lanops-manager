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
        Schema::table('event_participants', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable()->unique()->after('ticket_id');
        });

        // Existing tickets get a token too; run tickets:regenerate-qr afterwards so their QR codes use it
        DB::table('event_participants')->whereNull('qr_token')->orderBy('id')->chunkById(500, function ($participants) {
            foreach ($participants as $participant) {
                DB::table('event_participants')->where('id', $participant->id)->update(['qr_token' => Str::random(40)]);
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
        Schema::table('event_participants', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
