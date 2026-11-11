<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRefund extends Model
{
    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'purchase_refunds';

    protected $fillable = [
        'purchase_id',
        'refunded_by',
        'amount',
        'gateway_refund_id',
        'status',
        'reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /*
     * Relationships
     */
    public function purchase()
    {
        return $this->belongsTo('App\Models\Purchase', 'purchase_id');
    }

    public function refundedBy()
    {
        return $this->belongsTo('App\Models\User', 'refunded_by');
    }

    /**
     * Tickets cancelled by this refund
     */
    public function participants()
    {
        return $this->hasMany('App\Models\EventParticipant', 'purchase_refund_id')->withTrashed();
    }

    /**
     * Was the money sent back through the payment gateway (rather than recorded by hand)
     * @return boolean
     */
    public function wentThroughGateway()
    {
        return $this->gateway_refund_id != null;
    }
}
