<?php

namespace Tests\Feature;

use App\Models\EventParticipant;
use App\Models\EventSeating;
use App\Models\EventSeatingPlan;
use App\Models\EventTicket;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Admin refunds: money back through Stripe, and refunded tickets cancelled
 * (seat freed, QR code dead, place back on sale) but kept in the records.
 */
class RefundsTest extends TestCase
{
    protected function createPurchase(User $user, EventTicket $ticket, int $quantity = 2, array $attributes = []): Purchase
    {
        $purchase = Purchase::create(array_merge([
            'user_id'        => $user->id,
            'type'           => 'Stripe',
            'transaction_id' => 'ch_test_123',
            'token'          => 'pi_test_123',
            'status'         => 'Success',
            'basket'         => ['tickets' => [$ticket->id => $quantity], 'referral_discount' => false],
        ], $attributes));

        // Without events: skips the QR code and Discord webhook, which are not under test
        EventParticipant::withoutEvents(function () use ($user, $ticket, $purchase, $quantity) {
            for ($i = 0; $i < $quantity; $i++) {
                EventParticipant::create([
                    'user_id'     => $user->id,
                    'event_id'    => $ticket->event_id,
                    'ticket_id'   => $ticket->id,
                    'purchase_id' => $purchase->id,
                ]);
            }
        });

        return $purchase->fresh();
    }

    protected function fakeStripe(string $status = 'succeeded'): void
    {
        Http::fake([
            'api.stripe.com/*' => Http::response(['id' => 're_test_1', 'object' => 'refund', 'status' => $status]),
        ]);
    }

    protected function refund(Purchase $purchase, array $data, ?User $admin = null)
    {
        return $this->actingAs($admin ?: $this->createUser(['admin' => 1]))
            ->post('/admin/purchases/' . $purchase->id . '/refund', $data);
    }

    public function testRefundingOneTicketRefundsItsPriceThroughStripeAndCancelsIt()
    {
        $this->fakeStripe();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);
        $purchase = $this->createPurchase($this->createUser(), $ticket);
        $this->assertEquals(60, $purchase->total);
        [$refunded, $kept] = $purchase->participants->all();

        $this->refund($purchase, [
            'participants'   => [$refunded->id],
            'amount'         => '30.00',
            'cancel_tickets' => '1',
            'reason'         => 'Can no longer attend',
        ])->assertRedirect('/admin/purchases/' . $purchase->id)->assertSessionHas('alert-success');

        Http::assertSent(function (HttpRequest $request) use ($purchase) {
            return $request->url() == 'https://api.stripe.com/v1/refunds'
                && $request['payment_intent'] == 'pi_test_123'
                && $request['amount'] == 3000
                && $request->hasHeader('Authorization', 'Bearer sk_test_dummy')
                && $request->hasHeader('Idempotency-Key');
        });

        $purchase->refresh();
        $this->assertEquals(30, $purchase->refunded_total);
        $this->assertSame('Partially Refunded', $purchase->getRefundStatus());
        $this->assertSame('Success', $purchase->status);

        $refund = $purchase->refunds->first();
        $this->assertSame('re_test_1', $refund->gateway_refund_id);
        $this->assertSame('succeeded', $refund->status);
        $this->assertSame('Can no longer attend', $refund->reason);

        $this->assertSoftDeleted($refunded);
        $this->assertSame($refund->id, EventParticipant::withTrashed()->find($refunded->id)->purchase_refund_id);
        $this->assertNotSoftDeleted($kept);
        $this->assertCount(2, $purchase->allParticipants);
        $this->assertCount(1, $ticket->event->eventParticipants()->get());

        $this->actingAs($this->createUser(['admin' => 1]))
            ->get('/admin/purchases/' . $purchase->id)
            ->assertOk()
            ->assertSee('Partially Refunded')
            ->assertSee('Amount to refund')
            ->assertSee('Cancelled - ', false)
            ->assertSee('Can no longer attend');
    }

    public function testRefundingEverythingMarksThePurchaseRefunded()
    {
        $this->fakeStripe();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);
        $purchase = $this->createPurchase($this->createUser(), $ticket);

        $this->refund($purchase, [
            'participants'   => $purchase->participants->pluck('id')->all(),
            'amount'         => '60',
            'cancel_tickets' => '1',
        ])->assertSessionHas('alert-success');

        $purchase->refresh();
        $this->assertSame('Refunded', $purchase->getRefundStatus());
        $this->assertSame(0, $purchase->getRefundableInPence());
        $this->assertCount(0, $purchase->participants);

        $this->actingAs($this->createUser(['admin' => 1]))
            ->get('/admin/purchases/' . $purchase->id)
            ->assertOk()
            ->assertSee('Refund history')
            ->assertSee('re_test_1')
            ->assertSee('Nothing left to refund');
    }

    public function testCannotRefundMoreThanIsLeft()
    {
        $this->fakeStripe();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);
        $purchase = $this->createPurchase($this->createUser(), $ticket);

        $this->refund($purchase, ['amount' => '50', 'participants' => []]);
        $this->refund($purchase, ['amount' => '10.01', 'participants' => []])
            ->assertSessionHas('alert-danger');

        Http::assertSentCount(1);
        $this->assertEquals(50, $purchase->fresh()->refunded_total);
    }

    public function testAStripeErrorRecordsNothingAndCancelsNothing()
    {
        Http::fake([
            'api.stripe.com/*' => Http::response(['error' => ['message' => 'Charge ch_test_123 has already been refunded.']], 400),
        ]);
        $ticket = $this->createEventWithTicket([], ['price' => 30]);
        $purchase = $this->createPurchase($this->createUser(), $ticket);
        $participant = $purchase->participants->first();

        $this->refund($purchase, ['participants' => [$participant->id], 'amount' => '30', 'cancel_tickets' => '1'])
            ->assertSessionHas('alert-danger', 'Refund not made: Charge ch_test_123 has already been refunded.');

        $this->assertEquals(0, $purchase->fresh()->refunded_total);
        $this->assertCount(0, $purchase->refunds()->get());
        $this->assertNotSoftDeleted($participant);
    }

    public function testCancelledTicketFreesItsSeatAndItsQrCodeStopsWorking()
    {
        $this->fakeStripe();
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket(['status' => 'PUBLISHED'], ['price' => 30]);
        $purchase = $this->createPurchase($user, $ticket, 1);
        $participant = $purchase->participants->first();
        $participant->forceFill(['qr_token' => 'scan-me'])->saveQuietly();

        $plan = factory(EventSeatingPlan::class)->make();
        $plan->forceFill(['event_id' => $ticket->event_id, 'status' => 'PUBLISHED'])->save();
        // Creating a plan creates its seats
        $seat = EventSeating::where('event_seating_plan_id', $plan->id)->where('seat', 'A1')->firstOrFail();
        $seat->forceFill(['event_participant_id' => $participant->id])->save();

        $this->refund($purchase, ['participants' => [$participant->id], 'amount' => '30', 'cancel_tickets' => '1'])
            ->assertSessionHas('alert-success');

        $this->assertNull($seat->fresh()->event_participant_id);
        $this->actingAs($user)->get('/tickets/retrieve/scan-me')->assertNotFound();
        $this->assertNull($ticket->event->fresh()->getEventParticipant($user->id));
    }

    public function testGoodwillRefundWithoutCancellingKeepsTheTicket()
    {
        $this->fakeStripe();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);
        $purchase = $this->createPurchase($this->createUser(), $ticket, 1);
        $participant = $purchase->participants->first();

        $this->refund($purchase, ['participants' => [$participant->id], 'amount' => '5'])
            ->assertSessionHas('alert-success');

        $this->assertNotSoftDeleted($participant);
        $this->assertEquals(5, $purchase->fresh()->refunded_total);
    }

    public function testCancellingWithoutARefundDoesNotCallStripe()
    {
        Http::fake();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);
        $purchase = $this->createPurchase($this->createUser(), $ticket, 1);
        $participant = $purchase->participants->first();

        $this->refund($purchase, ['participants' => [$participant->id], 'amount' => '0', 'cancel_tickets' => '1'])
            ->assertSessionHas('alert-success');

        Http::assertNothingSent();
        $this->assertSoftDeleted($participant);
        $this->assertSame('cancelled', $purchase->refunds()->first()->status);
    }

    public function testSignedInTicketCannotBeCancelled()
    {
        $this->fakeStripe();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);
        $purchase = $this->createPurchase($this->createUser(), $ticket, 1);
        $participant = $purchase->participants->first();
        $participant->forceFill(['signed_in' => true])->saveQuietly();

        $this->refund($purchase, ['participants' => [$participant->id], 'amount' => '30', 'cancel_tickets' => '1'])
            ->assertSessionHas('alert-danger');

        Http::assertNothingSent();
        $this->assertNotSoftDeleted($participant);
    }

    public function testATicketFromAnotherPurchaseCannotBeCancelled()
    {
        $this->fakeStripe();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);
        $purchase = $this->createPurchase($this->createUser(), $ticket, 1);
        $other = $this->createPurchase($this->createUser(), $ticket, 1, ['token' => 'pi_other']);
        $otherParticipant = $other->participants->first();

        $this->refund($purchase, ['participants' => [$otherParticipant->id], 'amount' => '0', 'cancel_tickets' => '1'])
            ->assertSessionHas('alert-danger');

        $this->assertNotSoftDeleted($otherParticipant);
    }

    public function testPaypalRefundIsOnlyRecorded()
    {
        Http::fake();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);
        $purchase = $this->createPurchase($this->createUser(), $ticket, 1, [
            'type' => 'Paypal Express', 'transaction_id' => 'PAYID', 'token' => 'EC-123',
        ]);

        $this->refund($purchase, ['participants' => $purchase->participants->pluck('id')->all(), 'amount' => '30', 'cancel_tickets' => '1'])
            ->assertSessionHas('alert-success');

        Http::assertNothingSent();
        $refund = $purchase->refunds()->first();
        $this->assertNull($refund->gateway_refund_id);
        $this->assertSame('manual', $refund->status);
        $this->assertSame('Refunded', $purchase->fresh()->getRefundStatus());
    }

    public function testOnlyAdminsCanRefund()
    {
        Http::fake();
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);
        $purchase = $this->createPurchase($user, $ticket, 1);

        $this->refund($purchase, ['participants' => $purchase->participants->pluck('id')->all(), 'amount' => '30', 'cancel_tickets' => '1'], $user);

        Http::assertNothingSent();
        $this->assertEquals(0, $purchase->fresh()->refunded_total);
        $this->assertCount(1, $purchase->participants()->get());
    }

    public function testCancelledTicketFreesCapacity()
    {
        $this->fakeStripe();
        $ticket = $this->createEventWithTicket(['capacity' => 1], ['price' => 30]);
        $purchase = $this->createPurchase($this->createUser(), $ticket, 1);

        $this->refund($purchase, ['participants' => $purchase->participants->pluck('id')->all(), 'amount' => '30', 'cancel_tickets' => '1']);

        $this->assertSame(0, $ticket->event->eventParticipants()->count());
    }

    public function testDiscordRoleIsRemovedOnlyWhenTheirLastTicketIsCancelled()
    {
        config(['app.discord_bot_url' => 'https://bot.example.test', 'app.discord_bot_secret' => 'bot-secret']);
        $this->fakeStripe();
        $user = $this->createUser(['discord_id' => '555']);
        $ticket = $this->createEventWithTicket(['discord_link_enabled' => true, 'discord_role_id' => '777'], ['price' => 30]);
        $purchase = $this->createPurchase($user, $ticket);
        [$first, $second] = $purchase->participants->all();
        $removals = fn () => Http::recorded(fn ($request) => $request->url() == 'https://bot.example.test/participants/remove');

        $this->refund($purchase, ['participants' => [$first->id], 'amount' => '30', 'cancel_tickets' => '1']);
        $this->assertCount(0, $removals());

        $this->refund($purchase, ['participants' => [$second->id], 'amount' => '30', 'cancel_tickets' => '1']);
        $this->assertCount(1, $removals());
        [$request] = $removals()->first();
        $this->assertSame('555', $request['discord_id']);
        $this->assertSame('777', $request['role_id']);
    }
}
