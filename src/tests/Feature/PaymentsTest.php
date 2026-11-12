<?php

namespace Tests\Feature;

use App\Models\EventParticipant;
use App\Models\EventTicket;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Stripe\ApiRequestor;
use Tests\Support\FakeStripeApi;
use Tests\TestCase;

/**
 * The Stripe checkout path. Stripe's API is faked: everything after the
 * charge (purchase, participants, QR code) runs for real, because a failure
 * there means a customer is charged without getting a ticket.
 */
class PaymentsTest extends TestCase
{
    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);

        parent::tearDown();
    }

    /**
     * Answer Stripe API calls from the test instead of api.stripe.com.
     * A call the test did not set up fails the request.
     */
    protected function fakeStripe(): FakeStripeApi
    {
        $stripe = new FakeStripeApi();
        ApiRequestor::setHttpClient($stripe);

        return $stripe;
    }

    /**
     * A PaymentIntent as Stripe returns it
     */
    protected function paymentIntent(string $status, float $amount, string $id = 'pi_test_123', array $extra = []): array
    {
        return array_merge([
            'id'            => $id,
            'object'        => 'payment_intent',
            'status'        => $status,
            'amount'        => (int) round($amount * 100),
            'currency'      => 'gbp',
            'latest_charge' => 'ch_test_123',
        ], $extra);
    }

    protected function cardDetails(): array
    {
        return [
            'gateway'         => 'stripe',
            'card_first_name' => 'Test',
            'card_last_name'  => 'Buyer',
            'stripe_token'    => 'pm_card_visa',
        ];
    }

    /**
     * Session state after the buyer ticks the terms box on the review page.
     */
    protected function termsAccepted(): array
    {
        return ['payment_terms_accepted' => true];
    }

    public function testCheckoutPagesRenderWithABasket()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();

        $this->actingAs($user)->withSession($this->basketFor($ticket));

        $this->get('/payment/checkout')->assertOk()->assertSee($ticket->name);
        $this->get('/payment/review/stripe')->assertOk();
        $this->get('/payment/details/stripe')->assertOk();
    }

    public function testAddingATicketToTheBasketGoesToCheckout()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();

        $this->actingAs($user)
            ->post('/tickets/purchase/' . $ticket->id, ['user_id' => $user->id, 'quantity' => 2])
            ->assertRedirect('/payment/checkout')
            ->assertSessionHas(config('app.basket_name') . '.tickets', [$ticket->id => 2]);
    }

    public function testSuccessfulCardPaymentCreatesPurchaseTicketsAndQrCodes()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);

        $stripe = $this->fakeStripe()->respond('POST', '/v1/payment_intents', $this->paymentIntent('succeeded', 60));

        $response = $this->actingAs($user)
            ->withSession($this->basketFor($ticket, 2) + $this->termsAccepted())
            ->post('/payment/post', $this->cardDetails());

        // One call creates and confirms the payment, with the card from Stripe.js
        $sent = $stripe->sent('POST', '/v1/payment_intents');
        $this->assertCount(1, $sent);
        $this->assertSame(6000, $sent[0]['params']['amount']);
        $this->assertSame(strtolower(config('app.currency')), $sent[0]['params']['currency']);
        $this->assertSame('pm_card_visa', $sent[0]['params']['payment_method']);
        $this->assertSame('true', $sent[0]['params']['confirm']);
        $this->assertStringEndsWith('/payment/callback?gate=stripe&type=return', $sent[0]['params']['return_url']);
        $this->assertSame($user->id, $sent[0]['params']['metadata']['user_id']);
        $this->assertCount(1, $stripe->requests);

        $purchase = Purchase::first();
        $this->assertNotNull($purchase, 'No purchase was recorded for a successful charge');
        $response->assertRedirect('/payment/successful/' . $purchase->id);

        $this->assertSame($user->id, $purchase->user_id);
        $this->assertSame('Stripe', $purchase->type);
        $this->assertSame('ch_test_123', $purchase->transaction_id);
        $this->assertSame('pi_test_123', $purchase->token);
        $this->assertEquals(60, $purchase->total);

        $participants = EventParticipant::where('purchase_id', $purchase->id)->get();
        $this->assertCount(2, $participants);
        foreach ($participants as $participant) {
            $this->assertSame($ticket->event_id, $participant->event_id);
            $this->assertSame($user->id, $participant->user_id);
            $qr = $participant->getFirstMedia('qr');
            $this->assertNotNull($qr, 'Participant has no QR code');
            $this->assertSame('image/png', $qr->mime_type);
        }

        $this->get('/payment/successful/' . $purchase->id)
            ->assertOk()
            ->assertSee('Thank you for your Payment!');
    }

    public function testTicketForAHiddenEventIsNotCharged()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);
        // Any call to Stripe fails the test
        $stripe = $this->fakeStripe();
        // An admin unpublishes the event while the buyer is on the card page
        \App\Models\Event::withoutGlobalScopes()->whereKey($ticket->event_id)->update(['status' => 'DRAFT']);

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket) + $this->termsAccepted())
            ->post('/payment/post', $this->cardDetails())
            ->assertSessionHas('alert-danger', $ticket->name . ' is no longer on sale.');

        $this->assertSame(0, Purchase::count());
    }

    public function testDeclinedCardPaymentRecordsNothing()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();

        // Stripe answers a declined card with a card_error
        $this->fakeStripe()->respond('POST', '/v1/payment_intents', ['error' => [
            'type'    => 'card_error',
            'code'    => 'card_declined',
            'message' => 'Your card was declined.',
        ]], 402);

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket) + $this->termsAccepted())
            ->post('/payment/post', $this->cardDetails())
            ->assertRedirect('/payment/failed')
            ->assertSessionHas('alert-danger', 'Payment was UNSUCCESSFUL! - Please try again. Your card was declined.');

        $this->assertSame(0, Purchase::count());
        $this->assertSame(0, EventParticipant::count());
    }

    public function testGatewayErrorDuringChargeRecordsNothing()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();

        $this->fakeStripe()->respond('POST', '/v1/payment_intents', ['error' => [
            'type'    => 'api_error',
            'message' => 'Stripe is down',
        ]], 400);

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket) + $this->termsAccepted())
            ->from('/payment/details/stripe')
            ->post('/payment/post', $this->cardDetails())
            ->assertRedirect('/payment/details/stripe')
            ->assertSessionHas('alert-danger', 'Stripe is down');

        $this->assertSame(0, Purchase::count());
    }

    /**
     * Session state while the buyer is away at their bank's 3D Secure check.
     */
    protected function awaitingThreeDSecure(EventTicket $ticket, int $quantity = 1, string $intent = 'pi_3ds_123'): array
    {
        $basket = $this->basketFor($ticket, $quantity);

        return $basket + [
            'params'         => ['amount' => $ticket->price * $quantity, 'currency' => 'GBP'],
            'payment_basket' => $basket[config('app.basket_name')],
            'payment_intent' => $intent,
        ];
    }

    /**
     * Stripe after 3D Secure: the given PaymentIntent charged this amount.
     */
    protected function stripeAfterThreeDSecure(string $intent, float $amount, string $status = 'succeeded'): FakeStripeApi
    {
        return $this->fakeStripe()->respond('GET', '/v1/payment_intents/' . $intent, $this->paymentIntent($status, $amount, $intent));
    }

    public function testThreeDSecureReturnCompletesThePurchase()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);

        $this->stripeAfterThreeDSecure('pi_3ds_123', 30);

        $this->actingAs($user)
            ->withSession($this->awaitingThreeDSecure($ticket))
            ->get('/payment/callback?gate=stripe&type=return&payment_intent=pi_3ds_123')
            ->assertRedirect('/payment/successful/' . Purchase::firstOrFail()->id);

        $this->assertSame(1, EventParticipant::count());
    }

    public function testThreeDSecureReturnConfirmsAPaymentStartedBeforeTheUpdate()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);
        // Omnipay made PaymentIntents with manual confirmation, so one that was
        // at 3D Secure during the deploy comes back needing a confirm
        $stripe = $this->stripeAfterThreeDSecure('pi_3ds_123', 30, 'requires_confirmation')
            ->respond('POST', '/v1/payment_intents/pi_3ds_123/confirm', $this->paymentIntent('succeeded', 30, 'pi_3ds_123'));

        $this->actingAs($user)
            ->withSession($this->awaitingThreeDSecure($ticket))
            ->get('/payment/callback?gate=stripe&type=return&payment_intent=pi_3ds_123')
            ->assertRedirect('/payment/successful/' . Purchase::firstOrFail()->id);

        $this->assertCount(1, $stripe->sent('POST', '/v1/payment_intents/pi_3ds_123/confirm'));
        $this->assertSame(1, EventParticipant::count());
    }

    public function testFailedThreeDSecureRecordsNothing()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);
        // The bank turned the payment down, so Stripe wants another card
        $this->stripeAfterThreeDSecure('pi_3ds_123', 30, 'requires_payment_method');

        $this->actingAs($user)
            ->withSession($this->awaitingThreeDSecure($ticket))
            ->get('/payment/callback?gate=stripe&type=return&payment_intent=pi_3ds_123')
            ->assertRedirect('/payment/failed');

        $this->assertSame(0, Purchase::count());
        $this->assertSame(0, EventParticipant::count());
    }

    public function testPaymentIntentThatDidNotSucceedRecordsNothing()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();
        $this->fakeStripe()->respond('POST', '/v1/payment_intents', $this->paymentIntent('requires_payment_method', 30, 'pi_test_123', [
            'last_payment_error' => ['type' => 'card_error', 'message' => 'Your card has insufficient funds.'],
        ]));

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket) + $this->termsAccepted())
            ->post('/payment/post', $this->cardDetails())
            ->assertRedirect('/payment/failed')
            ->assertSessionHas('alert-danger', 'Payment was UNSUCCESSFUL! - Please try again. Your card has insufficient funds.');

        $this->assertSame(0, Purchase::count());
    }

    public function testThreeDSecureReturnStillIssuesTicketsIfTheEventWasHidden()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);
        $this->stripeAfterThreeDSecure('pi_3ds_123', 30);
        // The card has been charged by the time the buyer comes back from 3D Secure
        \App\Models\Event::withoutGlobalScopes()->whereKey($ticket->event_id)->update(['status' => 'DRAFT']);

        $this->actingAs($user)
            ->withSession($this->awaitingThreeDSecure($ticket))
            ->get('/payment/callback?gate=stripe&type=return&payment_intent=pi_3ds_123');

        $this->assertSame([$ticket->event_id], EventParticipant::pluck('event_id')->all());
    }

    public function testCardNeedingThreeDSecureRemembersItsPaymentIntent()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();

        $this->fakeStripe()->respond('POST', '/v1/payment_intents', $this->paymentIntent('requires_action', 60, 'pi_test_123', [
            'next_action' => [
                'type'            => 'redirect_to_url',
                'redirect_to_url' => ['url' => 'https://hooks.stripe.com/3d_secure/test', 'return_url' => 'https://localhost'],
            ],
        ]));

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket, 2) + $this->termsAccepted())
            ->post('/payment/post', $this->cardDetails())
            ->assertRedirect('https://hooks.stripe.com/3d_secure/test')
            ->assertSessionHas('payment_intent', 'pi_test_123')
            ->assertSessionHas('payment_basket.tickets', [$ticket->id => 2]);

        $this->assertSame(0, Purchase::count());
    }

    public function testChangingTheBasketDuringThreeDSecureOnlyIssuesWhatWasPaidFor()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);

        $this->stripeAfterThreeDSecure('pi_3ds_123', 30);

        // Paid for 1, then raised the basket to 2 in another tab
        $session = array_merge($this->awaitingThreeDSecure($ticket, 1), $this->basketFor($ticket, 2));

        $this->actingAs($user)
            ->withSession($session)
            ->get('/payment/callback?gate=stripe&type=return&payment_intent=pi_3ds_123');

        $this->assertSame(1, EventParticipant::count());
        $this->assertEquals(30, Purchase::firstOrFail()->total);
    }

    public function testCallbackForAnotherPaymentIntentIsRefused()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();

        // Any call to Stripe fails the test
        $stripe = $this->fakeStripe();

        $this->actingAs($user)
            ->withSession($this->awaitingThreeDSecure($ticket))
            ->get('/payment/callback?gate=stripe&type=return&payment_intent=pi_someone_else')
            ->assertRedirect('/payment/failed');

        $this->assertSame(0, Purchase::count());
    }

    public function testCallbackWithoutAPaymentInProgressIsRefused()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();

        // Any call to Stripe fails the test
        $stripe = $this->fakeStripe();

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket) + ['params' => ['amount' => 30.0, 'currency' => 'GBP']])
            ->get('/payment/callback?gate=stripe&type=return&payment_intent=pi_3ds_123')
            ->assertRedirect('/payment/failed');

        $this->assertSame(0, Purchase::count());
    }

    public function testCallbackChargingADifferentAmountRecordsNothing()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);

        $this->stripeAfterThreeDSecure('pi_3ds_123', 5);

        $this->actingAs($user)
            ->withSession($this->awaitingThreeDSecure($ticket))
            ->get('/payment/callback?gate=stripe&type=return&payment_intent=pi_3ds_123')
            ->assertRedirect('/payment/failed');

        $this->assertSame(0, Purchase::count());
        $this->assertSame(0, EventParticipant::count());
    }

    public function testCallbackForAnAlreadyRecordedPaymentIntentIsRefused()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);

        $this->stripeAfterThreeDSecure('pi_3ds_123', 30);
        $session = $this->awaitingThreeDSecure($ticket);

        $this->actingAs($user)->withSession($session)
            ->get('/payment/callback?gate=stripe&type=return&payment_intent=pi_3ds_123');
        $this->actingAs($user)->withSession($session)
            ->get('/payment/callback?gate=stripe&type=return&payment_intent=pi_3ds_123')
            ->assertRedirect('/payment/failed');

        $this->assertSame(1, Purchase::count());
        $this->assertSame(1, EventParticipant::count());
    }

    public function testCallbackLocksTheBuyerBeforeCheckingForAnEarlierPurchase()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);

        $this->stripeAfterThreeDSecure('pi_3ds_123', 30);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = strtolower($query->sql);
        });

        $this->actingAs($user)
            ->withSession($this->awaitingThreeDSecure($ticket))
            ->get('/payment/callback?gate=stripe&type=return&payment_intent=pi_3ds_123');

        // Parallel returns of one payment queue on this lock, so the second
        // one sees the first one's purchase and records nothing
        $lock = array_key_first(array_filter($queries, fn ($sql) => str_contains($sql, 'from `users`') && str_contains($sql, 'for update')));
        $check = array_key_first(array_filter($queries, fn ($sql) => str_contains($sql, 'from `purchases`') && str_contains($sql, '`token`')));
        $this->assertNotNull($lock);
        $this->assertNotNull($check);
        $this->assertLessThan($check, $lock);
        $this->assertSame(1, Purchase::count());
    }

    public function testSuccessPageOnlyShowsYourOwnPurchase()
    {
        $owner = $this->createUser();
        $ticket = $this->createEventWithTicket();
        $purchase = Purchase::create([
            'user_id'        => $owner->id,
            'type'           => 'Stripe',
            'transaction_id' => 'ch_owner',
            'token'          => 'pi_owner',
            'status'         => 'Success',
            'basket'         => $this->basketFor($ticket)[config('app.basket_name')],
        ]);

        $this->actingAs($this->createUser())
            ->withSession($this->basketFor($ticket) + ['params' => ['amount' => 30.0]])
            ->get('/payment/successful/' . $purchase->id)
            ->assertRedirect('/');
    }

    public function testSuccessPageShowsEachTicketsQrCodeAndLink()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();
        $purchase = Purchase::create([
            'user_id'        => $user->id,
            'type'           => 'Stripe',
            'transaction_id' => 'ch_qr',
            'token'          => 'pi_qr',
            'status'         => 'Success',
            'basket'         => $this->basketFor($ticket)[config('app.basket_name')],
        ]);
        // Without events: the QR image is added by hand below, so imagick isn't needed
        [$ready, $waiting] = EventParticipant::withoutEvents(fn () => [
            EventParticipant::create(['user_id' => $user->id, 'event_id' => $ticket->event_id, 'ticket_id' => $ticket->id, 'purchase_id' => $purchase->id]),
            EventParticipant::create(['user_id' => $user->id, 'event_id' => $ticket->event_id, 'ticket_id' => $ticket->id, 'purchase_id' => $purchase->id]),
        ]);
        $ready->addMediaFromString('png')->usingFileName('readyqr.png')->toMediaCollection('qr');

        $this->actingAs($user)
            ->withSession(['params' => ['amount' => 30.0]])
            ->get('/payment/successful/' . $purchase->id)
            ->assertOk()
            ->assertDontSee('QR Codes Generating')
            ->assertSee($ready->fresh()->getFirstMediaUrl('qr'), false)
            ->assertSee('href="/account/tickets/' . $ready->id . '"', false)
            ->assertSee('href="/account/tickets/' . $waiting->id . '"', false)
            ->assertSee("Your QR code isn't ready yet", false);
    }

    public function testCancelledCallbackRecordsNothing()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket))
            ->get('/payment/callback?gate=stripe&type=cancel')
            ->assertRedirect('/payment/cancelled');

        $this->assertSame(0, Purchase::count());
    }

    public function testSoldOutEventIsNotCharged()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket(['capacity' => 0]);

        // Any call to Stripe fails the test
        $stripe = $this->fakeStripe();

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket) + $this->termsAccepted())
            ->from('/payment/details/stripe')
            ->post('/payment/post', $this->cardDetails())
            ->assertRedirect('/payment/details/stripe')
            ->assertSessionHas('alert-danger', $ticket->event->display_name . ' has sold out!');

        $this->assertSame(0, Purchase::count());
    }

    public function testSoldOutTicketTypeIsNotCharged()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([], ['quantity' => 1]);
        EventParticipant::create([
            'user_id'   => $this->createUser()->id,
            'event_id'  => $ticket->event_id,
            'ticket_id' => $ticket->id,
        ]);

        // Any call to Stripe fails the test
        $stripe = $this->fakeStripe();

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket) + $this->termsAccepted())
            ->from('/payment/details/stripe')
            ->post('/payment/post', $this->cardDetails())
            ->assertRedirect('/payment/details/stripe')
            ->assertSessionHas('alert-danger', $ticket->name . ' has sold out!');

        $this->assertSame(0, Purchase::count());
    }

    public function testBasketBiggerThanTheSpacesLeftIsNotCharged()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket(['capacity' => 1]);

        // Any call to Stripe fails the test
        $stripe = $this->fakeStripe();

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket, 2) + $this->termsAccepted())
            ->post('/payment/post', $this->cardDetails())
            ->assertSessionHas('alert-danger', $ticket->event->display_name . ' has sold out!');

        $this->assertSame(0, Purchase::count());
    }

    public static function badQuantities(): array
    {
        return [
            'zero'      => [0],
            'negative'  => [-1],
            'decimal'   => [0.5],
            'too many'  => [EventTicket::MAX_PER_ORDER + 1],
            'not a number' => ['lots'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badQuantities')]
    public function testTicketQuantityMustBeSensible($quantity)
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();

        $this->actingAs($user)
            ->post('/tickets/purchase/' . $ticket->id, ['user_id' => $user->id, 'quantity' => $quantity])
            ->assertSessionHasErrors('quantity')
            ->assertSessionMissing(config('app.basket_name'));
    }

    public function testSoldOutTicketCannotBeAddedToTheBasket()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket(['capacity' => 1]);

        $this->actingAs($user)
            ->post('/tickets/purchase/' . $ticket->id, ['user_id' => $user->id, 'quantity' => 2])
            ->assertRedirect('/events/' . $ticket->event->slug)
            ->assertSessionHas('alert-danger', $ticket->event->display_name . ' has sold out!')
            ->assertSessionMissing(config('app.basket_name'));
    }

    public function testReviewPageNeedsTheTermsBoxTicked()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket))
            ->get('/payment/review/stripe')
            ->assertOk()
            ->assertSee('name="accept_terms"', false);

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket))
            ->post('/payment/post', ['gateway' => 'stripe'])
            ->assertRedirect('/payment/review/stripe')
            ->assertSessionHas('alert-danger')
            ->assertSessionMissing('payment_terms_accepted');

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket))
            ->post('/payment/post', ['gateway' => 'stripe', 'accept_terms' => '1'])
            ->assertRedirect('/payment/details/stripe')
            ->assertSessionHas('payment_terms_accepted', true);
    }

    public function testCardPaymentWithoutAcceptedTermsIsNotCharged()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();

        // Any call to Stripe fails the test
        $stripe = $this->fakeStripe();

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket))
            ->post('/payment/post', $this->cardDetails())
            ->assertRedirect('/payment/review/stripe');

        $this->assertSame(0, Purchase::count());
    }

    public function testShowingTheReviewPageClearsAcceptedTerms()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket) + $this->termsAccepted())
            ->get('/payment/review/stripe')
            ->assertOk()
            ->assertSessionMissing('payment_terms_accepted');
    }

    public function testCheckoutNeedsAVerifiedLogin()
    {
        $this->get('/payment/checkout')->assertRedirect();

        $this->actingAs($this->createUser(['email_verified_at' => null]))
            ->get('/payment/checkout')
            ->assertRedirect('/register/email/verify');
    }
}
