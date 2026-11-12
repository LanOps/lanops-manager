<?php

namespace App\Http\Controllers;

use Helpers;
use Auth;

use App\Models\User;
use App\Models\Purchase;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\EventParticipant;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

use Omnipay\Omnipay as Omnipay;

class PaymentsController extends Controller
{
    protected $sandbox = false;

    /**
     * Checkout Page
     * @return View
     */
    public function showCheckout()
    {
        if (!Session::has(config('app.basket_name'))) {
            return Redirect::to('/');
        }
        return view('payments.checkout')
            ->withBasket(Helpers::formatBasket(Session::get(config('app.basket_name'))))
            ->withActivePaymentGateways(config('laravel-omnipay.gateways.available_payment_gateways'))
        ;
    }

    /**
     * Review Terms and Conditions of Purchase Page
     * @return View
     */
    public function showReview($paymentGateway)
    {
        if (!$paymentGateway = $this->checkParams($paymentGateway, $basket = Session::get(config('app.basket_name')))) {
            return Redirect::back();
        }
        // The terms have to be ticked again each time the review page is shown
        Session::forget('payment_terms_accepted');
        $nextEventFlag = true;
        if (array_key_exists('tickets', $basket)) {
            $nextEvent = Event::where('end', '>=', \Carbon\Carbon::now())
                ->orderBy(\DB::raw('ABS(DATEDIFF(events.end, NOW()))'))
                ->first();
            foreach ($basket['tickets'] as $ticketId => $quantity) {
                // Either can be missing (no upcoming event, or the ticket's event
                // is hidden from visitors), which used to crash the review page
                $ticketEventId = EventTicket::where('id', $ticketId)->value('event_id');
                if (!$nextEvent || $ticketEventId != $nextEvent->id) {
                    $nextEventFlag = false;
                }
            }
        }
        return view('payments.review')
            ->withPaymentGateway($paymentGateway)
            ->withBasket(Helpers::formatBasket($basket))
            ->withNextEventFlag($nextEventFlag)
        ;
    }

    /**
     * Payment Details Page
     * @param  $paymentGateway
     * @return View
     */
    public function showDetails($paymentGateway)
    {
        if (!$paymentGateway = $this->checkParams($paymentGateway, $basket = Session::get(config('app.basket_name')))) {
            return Redirect::back();
        }
        $delivery = false;
        $deliveryDetails = false;
        if (array_key_exists('shop', $basket)) {
            $delivery = true;
            if (array_key_exists('delivery', $basket)) {
                $deliveryDetails = $basket['delivery'];
            }
        }
        return view('payments.details')
            ->withPaymentGateway($paymentGateway)
            ->withBasket(Helpers::formatBasket($basket))
            ->withDelivery($delivery)
            ->withDeliveryDetails($deliveryDetails)
        ;
    }

    /**
     * Post Payment to Gateway
     * @param  Request $request
     * @return Redirect
     */
    public function postPayment(Request $request)
    {
        if (!$paymentGateway = $this->checkParams($request->gateway, $basket = Session::get(config('app.basket_name')))) {
            return Redirect::back();
        }
        // The terms tick box on the review page must be ticked before any payment
        if ($request->boolean('accept_terms')) {
            Session::put('payment_terms_accepted', true);
        }
        if (!Session::get('payment_terms_accepted')) {
            Session::flash('alert-danger', 'Please tick the box to agree to the Terms and Conditions');
            return Redirect::to('/payment/review/' . $paymentGateway);
        }
        if (array_key_exists('tickets', $basket)) {
            foreach ($basket['tickets'] as $ticketId => $quantity) {
                $ticket = EventTicket::where('id', $ticketId)->first();
                if ($soldOut = $ticket->soldOutMessage($quantity)) {
                    Session::flash('alert-danger', $soldOut);
                    return Redirect::back();
                }
            }
        }
        if ((array_key_exists('codes', $basket) && array_key_exists('referral', $basket['codes']) && !Auth::user()->isReferrable()) ||
            $basket['referral_discount'] && !Auth::user()->getAvailableReferralPurchase()
            ) {
            Session::flash('alert-danger', 'Basket has changed');
            return Redirect::to('/payment/checkout');
        }
        
        // Check if the card details have been submitted
        if (!isset($request->card_first_name) &&
            !isset($request->card_last_name) &&
            !isset($request->stripe_token)
        ) {
            return Redirect::to('/payment/details/' . $paymentGateway);
        }

        $processPaymentSkip = false;

        switch ($paymentGateway) {
            case 'stripe':
                // Stripe Post Params
                $rules = [
                    'card_first_name'   => 'required',
                    'card_last_name'    => 'required',
                    'stripe_token'      => 'required|filled',
                ];
                $messages = [
                    'card_first_name.required'      => 'Card First Name is Required',
                    'card_last_name.required'       => 'Card Last Name is Required',
                    'stripe_token.required'         => 'Stripe Token is Required',
                    'stripe_token.filled'           => 'Stripe Token cannot be empty',
                ];
                $this->validate($request, $rules, $messages);
                $params = array(
                    'cancelUrl'     => $this->getCallbackCancelUrl($paymentGateway),
                    'returnUrl'     => $this->getCallbackReturnUrl($paymentGateway),
                    'amount'        => (float)Helpers::formatBasket($basket)->total,
                    'description'   => 'Purchase for ' . config('app.name'),
                    'currency'      => config('app.currency'),
                    'paymentMethod' => $request->stripe_token,
                    'confirm'       => true,
                );
                $gateway = Omnipay::create('Stripe\PaymentIntents');
                $gateway->setApiKey(config('laravel-omnipay.gateways.stripe.credentials.secret'));
                break;
        }
        Session::put('params', $params);
        // Tickets are issued from this copy of the basket, so changing the
        // basket in another tab during 3D Secure changes nothing
        Session::put('payment_basket', $basket);
        Session::forget('payment_intent');
        Session::save();
        if (!$processPaymentSkip) {
            if (config('app.debug')) {
                $this->sandbox = true;
            }
            $gateway->setTestMode($this->sandbox);

            // Send Payment
            try {
                $response = $gateway->purchase($params)->send();
            } catch (\Exception $e) {
                Session::flash('alert-danger', $e->getMessage());
                return Redirect::back();
            }
        }

        if ($response->isSuccessful()) {
            // Payment was successful (purchase was sent with confirm): update database
            $purchaseParams = [
                'user_id'                   => Auth::id(),
                'type'                      => 'Stripe',
                'transaction_id'            => $response->getTransactionReference(),
                'token'                     => $response->getPaymentIntentReference(),
                'status'                    => 'Success',
                'basket'                    => $basket,
            ];
            $purchase = Purchase::create($purchaseParams);
            $this->processBasket($basket, $purchase->id);
            return Redirect::to('/payment/successful/' . $purchase->id);
        } elseif ($response->isRedirect()) {
            // Payment Requires redirect; only this payment may complete on return
            if ($paymentGateway == 'stripe') {
                Session::put('payment_intent', $response->getPaymentIntentReference());
                Session::save();
            }
            try {
                $response->redirect();
            } catch (\Exception $e) {
                Session::flash('alert-danger', $e->getMessage());
                return Redirect::back();
            }
        }

        //Failed transaction
        $message = '';
        if (!$processPaymentSkip) {
            $message = $response->getMessage();
        }
        Session::flash('alert-danger', 'Payment was UNSUCCESSFUL! - Please try again.' . $message);
        return Redirect::to('/payment/failed');
    }

    /**
     * Process Callback Payment
     * @param  Request $request
     * @return Redirect
     */
    public function processCallback(Request $request)
    {
        if (!$paymentGateway = $this->checkParams($request->gate, $basket = Session::get(config('app.basket_name')))) {
            return Redirect::back();
        }
        if ($request->input('type') == 'cancel') {
            Session::flash('alert-danger', 'Payment was CANCELLED!');
            return Redirect::to('/payment/cancelled');
        }
        if (!Session::has('params') || !Session::has('payment_basket')) {
            Session::flash('alert-danger', 'Payment was UNSUCCESSFUL!');
            return Redirect::to('/payment/failed');
        }
        $params = Session::get('params');
        $basket = Session::get('payment_basket');
        if (config('app.debug')) {
            $this->sandbox = true;
        }
        // The same return can arrive several times at once (double click, reload,
        // or on purpose). Lock the buyer's row so only one request at a time can
        // check, confirm and record the payment; the rest then see it recorded.
        // Tickets are issued after the lock, so a ticket error can't undo the record.
        $purchase = DB::transaction(function () use ($request, $paymentGateway, $params, $basket) {
            User::whereKey(Auth::id())->lockForUpdate()->first();
            return $this->recordCallbackPayment($request, $paymentGateway, $params, $basket);
        });
        if ($purchase) {
            $this->processBasket($basket, $purchase->id);
            return Redirect::to('/payment/successful/' . $purchase->id);
        }
        //Failed transaction
        Session::flash('alert-danger', 'Payment was UNSUCCESSFUL! - Please try again.');
        return Redirect::to('/payment/failed');
    }

    /**
     * Confirm a returning payment with the gateway and record its purchase
     * @param  Request $request
     * @param  $paymentGateway
     * @param  $params
     * @param  $basket
     * @return Purchase|null
     */
    private function recordCallbackPayment(Request $request, $paymentGateway, $params, $basket)
    {
        $successful = false;
        switch ($paymentGateway) {
            case 'stripe':
                $paymentIntent = $request->get('payment_intent');
                if (!$paymentIntent ||
                    $paymentIntent !== Session::get('payment_intent') ||
                    Purchase::where('token', $paymentIntent)->exists()
                ) {
                    break;
                }
                $gateway = Omnipay::create('Stripe\PaymentIntents');
                $gateway->setApiKey(config('laravel-omnipay.gateways.stripe.credentials.secret'));

                //Complete Purchase
                $response = $gateway->confirm([
                    'paymentIntentReference' => $paymentIntent,
                    'returnUrl'     => $this->getCallbackReturnUrl($paymentGateway),
                ])->send();

                if ($response->isSuccessful() && $this->chargedAmountMatches($response->getData(), $params)) {
                    //Add Purchase to database
                    $purchaseParams = [
                        'user_id'                   => Auth::id(),
                        'type'                      => 'Stripe',
                        'transaction_id'            => $response->getTransactionReference(),
                        'token'                     => $response->getPaymentIntentReference(),
                        'status'                    => 'Success',
                        'basket'                    => $basket,
                    ];
                    $successful = true;
                }
                break;
        }
        if (!$successful) {
            return null;
        }
        return Purchase::create($purchaseParams);
    }

    /**
     * Successful Payment Page
     * @param  Purchase $purchase
     * @return View
     */
    public function showSuccessful(Purchase $purchase)
    {
        if (!Session::has('params') || $purchase->user_id != Auth::id()) {
            return Redirect::to('/');
        }
        $basket = $purchase->basket;
        $type = 'tickets';
        if (array_key_exists('shop', $basket)) {
            $type = 'shop';
        }
        $basket = Helpers::formatBasket($basket, null, $purchase->referral_discount_total, true);
        Session::forget('params');
        Session::forget(config('app.basket_name'));
        Session::forget('payment_terms_accepted');
        Session::forget(['payment_basket', 'payment_intent']);
        return view('payments.successful')
            ->withType($type)
            ->withBasket($basket)
            ->withPurchase($purchase)
        ;
    }

    /**
     * Failed Payment Page
     * @param  Purchase $purchase
     * @return View
     */
    public function showFailed()
    {
        Session::forget('params');
        Session::forget(config('app.basket_name'));
        Session::forget('payment_terms_accepted');
        Session::forget(['payment_basket', 'payment_intent']);
        return view('payments.failed');
    }

    /**
     * Cancelled Payment Page
     * @param  Purchase $purchase
     * @return View
     */
    public function showCancelled()
    {
        Session::forget('params');
        Session::forget(config('app.basket_name'));
        Session::forget('payment_terms_accepted');
        Session::forget(['payment_basket', 'payment_intent']);
        return view('payments.cancelled');
    }

    /**
     * Process Basket for Successful Order
     * @param  $basket
     * @param  $purchaseId
     */
    private function processBasket($basket, $purchaseId)
    {
        $user = Auth::user();
        if (array_key_exists('tickets', $basket)) {
            foreach ($basket['tickets'] as $ticketId => $quantity) {
                $ticket = EventTicket::where('id', $ticketId)->first();
                for ($i = 1; $i <= $quantity; $i++) {
                    //Add Participant to database
                    $participant = [
                        'user_id'       => $user->id,
                        // event_id, not event->id: the event may have been hidden since the
                        // basket was filled, and the payment has already been taken by now
                        'event_id'      => $ticket->event_id,
                        'ticket_id'     => $ticket->id,
                        'purchase_id'   => $purchaseId,
                    ];
                    EventParticipant::create($participant);
                }
            }
        }
    }

    /**
     * Check Params for the Order are correct
     * @param  $paymentGateway
     * @param  $basket
     */
    private function checkParams($paymentGateway, $basket)
    {
        $acceptedPaymentGateways = config('laravel-omnipay.gateways.available_payment_gateways') ?: [];
        if (in_array(strtolower($paymentGateway), $acceptedPaymentGateways)) {
            $paymentGateway = strtolower($paymentGateway);
        } else {
            Session::flash('alert-danger', 'A Payment Gateway is required: ' . implode(" ", $acceptedPaymentGateways));
            return false;
        }
        if (!$basket = Session::get(config('app.basket_name'))) {
            Session::flash('alert-danger', 'No Basket was found. Please try again');
            return false;
        }
        if (!isset($paymentGateway)) {
            Session::flash('alert-danger', 'A Payment Gateway is required: ' . implode(" ", $acceptedPaymentGateways));
            return false;
        }

        return $paymentGateway;
    }

    /**
     * Get Callback Return Url
     * @param  $paymentGateway
     * @return String
     */
    private function getCallbackReturnUrl($paymentGateway)
    {
        return rtrim(config('app.url'), '/') . '/payment/callback?gate=' . $paymentGateway . '&type=return';
    }

    /**
     * Get Callback Cancel Url
     * @param  $paymentGateway
     * @return String
     */
    private function getCallbackCancelUrl($paymentGateway)
    {
        return rtrim(config('app.url'), '/') . '/payment/callback?gate=' . $paymentGateway . '&type=cancel';
    }

    /**
     * Check Stripe charged the amount and currency this payment was started for
     * @param  array $paymentIntent
     * @param  array $params
     * @return Boolean
     */
    private function chargedAmountMatches($paymentIntent, $params)
    {
        return isset($paymentIntent['amount'], $paymentIntent['currency']) &&
            (int) $paymentIntent['amount'] === (int) round($params['amount'] * 100) &&
            strtolower($paymentIntent['currency']) === strtolower($params['currency']);
    }

    public function applyDiscountCode(Request $request)
    {
        $rules = [
            'referral_code'     => 'filled',
        ];
        $messages = [
            'referral_code.filled'      => 'Referral Code Cannot be blank.',
        ];
        $this->validate($request, $rules, $messages);
        if (!User::isValidReferralCode($request->referral_code, Auth::user())) {
            Session::flash('alert-danger', 'Referral Code is not valid');
            return Redirect::back();
        }

        $basket = Session::get(config('app.basket_name'));
        $basket['codes']['referral'] = $request->referral_code;
        Session::put(config('app.basket_name'), $basket);

        Session::flash('alert-success', 'Referral Code applied');
        return Redirect::back();
    }
}
