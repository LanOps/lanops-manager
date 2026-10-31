<?php

namespace App\Models;

use Helpers;

use App\Exceptions\RefundException;
use App\Libraries\StripeRefunds;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Purchase extends Model
{
    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'purchases';

    protected $fillable = [
        'user_id',
        'type',
        'transaction_id',
        'token',
        'status',
        'paypal_email',
        'basket',
        'referral_discount_total',
        'referral_code_user_id',
        'referral_code_discount_redeemed_purchase_id',
        'total'
    ];

    protected $casts = [
        'basket' => 'array',
        'refunded_total' => 'decimal:2',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = array(
        'created_at',
        'updated_at'
    );

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $formattedBasket = Helpers::formatBasket($model->basket);
            $model->total = $formattedBasket->total;
            $model->total_before_discount = $formattedBasket->total_before_discounts;
            $model->referral_discount_total = $formattedBasket->referral_discount_total;
            if ($formattedBasket->referral_code && $referralUser = User::getuserByReferralCode($formattedBasket->referral_code)) {
                $model->referral_code_user_id = $referralUser->id;
            }
        });
        self::created(function ($model) {
            $formattedBasket = Helpers::formatBasket($model->basket);
            if ($model->referral_discount_total > 0 && $formattedBasket->referral_used && $referredPurchase = $model->user->getAvailableReferralPurchase()) {
                $referredPurchase->referral_code_discount_redeemed_purchase_id = $model->id;
                $referredPurchase->save();
            }
        });
    }
    /*
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function participants()
    {
        return $this->hasMany('App\Models\EventParticipant', 'purchase_id');
    }

    /**
     * Every ticket bought in this purchase, including ones cancelled by a refund
     */
    public function allParticipants()
    {
        return $this->hasMany('App\Models\EventParticipant', 'purchase_id')->withTrashed();
    }

    public function refunds()
    {
        return $this->hasMany('App\Models\PurchaseRefund', 'purchase_id')->orderBy('id');
    }

    public function referralUser()
    {
        return $this->belongsTo('App\Models\User', 'referral_code_user_id');
    }

    public function referralDiscountUsedPurchase()
    {
        return $this->belongsTo('App\Models\Purchase', 'referral_code_discount_redeemed_purchase_id');
    }

    public function referralCodeUsedPurchase()
    {
        return $this->hasOne('App\Models\Purchase', 'referral_code_discount_redeemed_purchase_id');
    }

    /**
     * Get Purchase Type
     * @param String
     */
    public function getPurchaseType()
    {
        switch (strtolower($this->type)) {
            case 'stripe':
                return 'Card';
                break;
            case 'paypal express':
                return 'Paypal';
                break;
            default:
                return $this->type;
                break;
        }
    }

    /**
     * Was this purchase paid by card through Stripe
     * @return boolean
     */
    public function isStripe()
    {
        return strtolower($this->type) == 'stripe';
    }

    /**
     * What the customer paid, in pence
     * Purchases from before totals were stored have a total of 0, so add up their tickets instead
     * @return int
     */
    public function getPaidTotalInPence()
    {
        if ((float) $this->total > 0) {
            return (int) round($this->total * 100);
        }
        $pence = 0;
        foreach ($this->allParticipants as $participant) {
            if ($participant->ticket && $participant->ticket->price > 0) {
                $pence += (int) round($participant->ticket->price * 100);
            }
        }
        return $pence;
    }

    /**
     * How much is left to refund, in pence
     * @return int
     */
    public function getRefundableInPence()
    {
        return max(0, $this->getPaidTotalInPence() - (int) round($this->refunded_total * 100));
    }

    /**
     * Refund state for display: null, 'Partially Refunded' or 'Refunded'
     * @return string|null
     */
    public function getRefundStatus()
    {
        if ((float) $this->refunded_total <= 0) {
            return null;
        }
        return $this->getRefundableInPence() > 0 ? 'Partially Refunded' : 'Refunded';
    }

    /**
     * Refund money and/or cancel tickets on this purchase
     *
     * Stripe purchases are refunded through Stripe. Anything else (PayPal,
     * free) is only recorded here; the money has to be sent back by hand.
     *
     * @param  array       $participantIds Tickets on this purchase to cancel
     * @param  int         $amountInPence  Money to give back, 0 to only cancel
     * @param  bool        $cancelTickets  Cancel the tickets (free the seat, stop the QR code)
     * @param  User        $admin
     * @param  string|null $reason
     * @return PurchaseRefund
     * @throws RefundException
     */
    public function refund(array $participantIds, int $amountInPence, bool $cancelTickets, User $admin, ?string $reason = null)
    {
        return DB::transaction(function () use ($participantIds, $amountInPence, $cancelTickets, $admin, $reason) {
            // Lock the purchase so two refunds can't both spend what's left
            $purchase = self::whereKey($this->id)->lockForUpdate()->firstOrFail();

            $participantIds = array_values(array_unique(array_map('intval', $participantIds)));
            $participants = $purchase->participants()->whereIn('id', $participantIds)->get();
            if ($participants->count() != count($participantIds)) {
                throw new RefundException('One or more of those tickets is not on this purchase or is already cancelled.');
            }
            if ($amountInPence < 0) {
                throw new RefundException('The refund amount can\'t be negative.');
            }
            $refundable = $purchase->getRefundableInPence();
            if ($amountInPence > $refundable) {
                throw new RefundException(
                    'That is more than is left to refund (' . config('app.currency_symbol') . number_format($refundable / 100, 2) . ').'
                );
            }
            if ($cancelTickets) {
                if ($participants->isEmpty()) {
                    throw new RefundException('Choose the tickets to cancel.');
                }
                if ($participants->contains('signed_in', true)) {
                    throw new RefundException('A ticket that is signed in at the event can\'t be cancelled.');
                }
            } elseif ($amountInPence == 0) {
                throw new RefundException('Enter an amount to refund, or tick Cancel tickets.');
            }

            $gatewayRefundId = null;
            $status = $amountInPence > 0 ? 'manual' : 'cancelled';
            if ($amountInPence > 0 && $purchase->isStripe()) {
                // The same request again (a double click, or a retry after a
                // failed save) gets Stripe's first refund back, not a second one
                $idempotencyKey = 'purchase-' . $purchase->id . '-refund-' . ($purchase->refunds()->count() + 1)
                    . '-' . $amountInPence . '-' . implode('.', $participantIds);
                $stripeRefund = StripeRefunds::refund($purchase, $amountInPence, $idempotencyKey);
                $gatewayRefundId = $stripeRefund['id'];
                $status = $stripeRefund['status'] ?? 'succeeded';
            }

            $refund = $purchase->refunds()->create([
                'refunded_by'       => $admin->id,
                'amount'            => $amountInPence / 100,
                'gateway_refund_id' => $gatewayRefundId,
                'status'            => $status,
                'reason'            => $reason,
            ]);

            $purchase->refunded_total = ((int) round($purchase->refunded_total * 100) + $amountInPence) / 100;
            $purchase->save();

            if ($cancelTickets) {
                foreach ($participants as $participant) {
                    $participant->cancel($refund);
                }
            }

            $this->refresh();
            return $refund;
        });
    }
}
