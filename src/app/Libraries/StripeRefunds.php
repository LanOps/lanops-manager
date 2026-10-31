<?php

namespace App\Libraries;

use App\Models\Purchase;
use Illuminate\Support\Facades\Http;
use App\Exceptions\RefundException;

/**
 * Refunds a Stripe payment through the Stripe API.
 *
 * Omnipay's Stripe refund request needs a charge id and is built for the
 * older Charges API, so this calls the Refunds endpoint directly with the
 * PaymentIntent the purchase was paid with.
 */
class StripeRefunds
{
    const REFUNDS_URL = 'https://api.stripe.com/v1/refunds';

    /**
     * Refund part or all of a purchase
     * @param  Purchase $purchase
     * @param  int      $amountInPence
     * @param  string   $idempotencyKey Stripe returns the first refund again for a repeat of the same key
     * @return array    The Stripe refund object
     * @throws RefundException when Stripe does not accept the refund
     */
    public static function refund(Purchase $purchase, int $amountInPence, string $idempotencyKey)
    {
        $params = [
            'amount'                => $amountInPence,
            'metadata[purchase_id]' => $purchase->id,
        ];
        if (str_starts_with((string) $purchase->token, 'pi_')) {
            $params['payment_intent'] = $purchase->token;
        } elseif (str_starts_with((string) $purchase->transaction_id, 'ch_')) {
            $params['charge'] = $purchase->transaction_id;
        } else {
            throw new RefundException('This purchase has no Stripe payment to refund.');
        }

        try {
            $response = Http::withToken(config('laravel-omnipay.gateways.stripe.credentials.secret'))
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->asForm()
                ->timeout(30)
                ->post(self::REFUNDS_URL, $params);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new RefundException('Could not reach Stripe. No refund was made, please try again.');
        }

        $refund = $response->json() ?: [];
        if (!$response->successful() || !isset($refund['id'])) {
            $message = $refund['error']['message'] ?? 'Stripe did not accept the refund (HTTP ' . $response->status() . ').';
            throw new RefundException($message);
        }
        if (in_array($refund['status'] ?? '', ['failed', 'canceled'])) {
            throw new RefundException('Stripe refund ' . $refund['id'] . ' ' . $refund['status'] . '.');
        }

        return $refund;
    }
}
