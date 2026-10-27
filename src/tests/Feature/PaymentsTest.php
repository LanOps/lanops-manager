<?php

namespace Tests\Feature;

use App\Models\EventParticipant;
use App\Models\EventTicket;
use App\Models\Purchase;
use Mockery;
use Omnipay\Common\GatewayFactory;
use Omnipay\Common\GatewayInterface;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Message\ResponseInterface;
use Omnipay\Omnipay;
use Tests\TestCase;

/**
 * The Stripe checkout path. The gateway is mocked: everything after the
 * charge (purchase, participants, QR code) runs for real, because a failure
 * there means a customer is charged without getting a ticket.
 */
class PaymentsTest extends TestCase
{
    protected function tearDown(): void
    {
        Omnipay::setFactory(null);

        parent::tearDown();
    }

    /**
     * Swap Omnipay's gateway factory for one that returns the given gateway.
     */
    protected function fakeGateway(GatewayInterface $gateway): void
    {
        $factory = Mockery::mock(GatewayFactory::class);
        $factory->shouldReceive('create')->with('Stripe\PaymentIntents')->andReturn($gateway);
        Omnipay::setFactory($factory);
    }

    protected function stripeResponse(bool $successful, bool $redirect = false, string $message = '', array $data = [], string $intent = 'pi_test_123'): ResponseInterface
    {
        $response = Mockery::mock($redirect ? RedirectResponseInterface::class : ResponseInterface::class);
        $response->shouldReceive('isSuccessful')->andReturn($successful);
        $response->shouldReceive('isRedirect')->andReturn($redirect);
        $response->shouldReceive('getMessage')->andReturn($message);
        $response->shouldReceive('getTransactionReference')->andReturn('ch_test_123');
        $response->shouldReceive('getPaymentIntentReference')->andReturn($intent);
        $response->shouldReceive('getData')->andReturn($data);

        return $response;
    }

    protected function sends(ResponseInterface $response): RequestInterface
    {
        $request = Mockery::mock(RequestInterface::class);
        $request->shouldReceive('send')->andReturn($response);

        return $request;
    }

    protected function stripeGateway(): Mockery\MockInterface
    {
        $gateway = Mockery::mock(GatewayInterface::class);
        $gateway->shouldReceive('setApiKey')->with('sk_test_dummy');
        $gateway->shouldReceive('setTestMode');

        return $gateway;
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

        $gateway = $this->stripeGateway();
        $gateway->shouldReceive('purchase')->once()
            ->with(Mockery::on(fn ($params) => $params['amount'] == 60.0
                && $params['currency'] == config('app.currency')
                && $params['paymentMethod'] == 'pm_card_visa'
                && $params['confirm'] === true))
            ->andReturn($this->sends($this->stripeResponse(true)));
        // purchase is sent with confirm, so a second confirm is not needed
        $gateway->shouldNotReceive('confirm');
        $this->fakeGateway($gateway);

        $response = $this->actingAs($user)
            ->withSession($this->basketFor($ticket, 2) + $this->termsAccepted())
            ->post('/payment/post', $this->cardDetails());

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

    public function testDeclinedCardPaymentRecordsNothing()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();

        $gateway = $this->stripeGateway();
        $gateway->shouldReceive('purchase')->once()
            ->andReturn($this->sends($this->stripeResponse(false, false, 'Your card was declined.')));
        $gateway->shouldNotReceive('confirm');
        $this->fakeGateway($gateway);

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket) + $this->termsAccepted())
            ->post('/payment/post', $this->cardDetails())
            ->assertRedirect('/payment/failed');

        $this->assertSame(0, Purchase::count());
        $this->assertSame(0, EventParticipant::count());
    }

    public function testGatewayErrorDuringChargeRecordsNothing()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();

        $gateway = $this->stripeGateway();
        $gateway->shouldReceive('purchase')->andThrow(new \RuntimeException('Stripe is down'));
        $this->fakeGateway($gateway);

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
     * A gateway whose confirm of the given payment intent charged this amount.
     */
    protected function confirmingGateway(string $intent, float $amount, bool $successful = true): Mockery\MockInterface
    {
        $gateway = $this->stripeGateway();
        $gateway->shouldReceive('confirm')->once()
            ->with(Mockery::on(fn ($params) => $params['paymentIntentReference'] == $intent))
            ->andReturn($this->sends($this->stripeResponse(
                $successful,
                false,
                '',
                ['amount' => (int) round($amount * 100), 'currency' => 'gbp'],
                $intent
            )));

        return $gateway;
    }

    public function testThreeDSecureReturnCompletesThePurchase()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);

        $this->fakeGateway($this->confirmingGateway('pi_3ds_123', 30));

        $this->actingAs($user)
            ->withSession($this->awaitingThreeDSecure($ticket))
            ->get('/payment/callback?gate=stripe&type=return&payment_intent=pi_3ds_123')
            ->assertRedirect('/payment/successful/' . Purchase::firstOrFail()->id);

        $this->assertSame(1, EventParticipant::count());
    }

    public function testCardNeedingThreeDSecureRemembersItsPaymentIntent()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();

        $response = $this->stripeResponse(false, true);
        $response->shouldReceive('redirect');
        $gateway = $this->stripeGateway();
        $gateway->shouldReceive('purchase')->once()->andReturn($this->sends($response));
        $this->fakeGateway($gateway);

        $this->actingAs($user)
            ->withSession($this->basketFor($ticket, 2) + $this->termsAccepted())
            ->post('/payment/post', $this->cardDetails())
            ->assertSessionHas('payment_intent', 'pi_test_123')
            ->assertSessionHas('payment_basket.tickets', [$ticket->id => 2]);

        $this->assertSame(0, Purchase::count());
    }

    public function testChangingTheBasketDuringThreeDSecureOnlyIssuesWhatWasPaidFor()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);

        $this->fakeGateway($this->confirmingGateway('pi_3ds_123', 30));

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

        $gateway = $this->stripeGateway();
        $gateway->shouldNotReceive('confirm');
        $this->fakeGateway($gateway);

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

        $gateway = $this->stripeGateway();
        $gateway->shouldNotReceive('confirm');
        $this->fakeGateway($gateway);

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

        $this->fakeGateway($this->confirmingGateway('pi_3ds_123', 5));

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

        $this->fakeGateway($this->confirmingGateway('pi_3ds_123', 30));
        $session = $this->awaitingThreeDSecure($ticket);

        $this->actingAs($user)->withSession($session)
            ->get('/payment/callback?gate=stripe&type=return&payment_intent=pi_3ds_123');
        $this->actingAs($user)->withSession($session)
            ->get('/payment/callback?gate=stripe&type=return&payment_intent=pi_3ds_123')
            ->assertRedirect('/payment/failed');

        $this->assertSame(1, Purchase::count());
        $this->assertSame(1, EventParticipant::count());
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

        $gateway = $this->stripeGateway();
        $gateway->shouldNotReceive('purchase');
        $this->fakeGateway($gateway);

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

        $gateway = $this->stripeGateway();
        $gateway->shouldNotReceive('purchase');
        $this->fakeGateway($gateway);

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

        $gateway = $this->stripeGateway();
        $gateway->shouldNotReceive('purchase');
        $this->fakeGateway($gateway);

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

        $gateway = $this->stripeGateway();
        $gateway->shouldNotReceive('purchase');
        $this->fakeGateway($gateway);

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
