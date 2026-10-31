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
        Schema::create('purchase_refunds', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('purchase_id')->unsigned()->index();
            $table->integer('refunded_by')->unsigned()->nullable()->index();
            $table->decimal('amount', 10, 2);
            // Stripe refund id (re_...); null for refunds recorded by hand
            $table->string('gateway_refund_id')->nullable();
            $table->string('status');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('cascade');
            $table->foreign('refunded_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('refunded_total', 10, 2)->default(0)->after('total_before_discount');
        });

        // Cancelled (refunded) tickets are soft deleted: kept for the records,
        // but gone from capacity counts, seating, QR scans and ticket lists
        Schema::table('event_participants', function (Blueprint $table) {
            $table->integer('purchase_refund_id')->unsigned()->nullable()->after('purchase_id');
            $table->softDeletes();

            $table->foreign('purchase_refund_id')->references('id')->on('purchase_refunds')->nullOnDelete();
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
            $table->dropForeign(['purchase_refund_id']);
            $table->dropColumn(['purchase_refund_id', 'deleted_at']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('refunded_total');
        });

        Schema::dropIfExists('purchase_refunds');
    }
};
