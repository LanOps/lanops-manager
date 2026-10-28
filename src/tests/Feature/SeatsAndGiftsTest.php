<?php

namespace Tests\Feature;

use App\Models\EventParticipant;
use App\Models\EventSeatingPlan;
use App\Models\EventTicket;
use App\Models\User;
use Tests\TestCase;

/**
 * Seating and gifting act on someone's ticket, so only that ticket's owner
 * (or the person who gifted it) may do them.
 */
class SeatsAndGiftsTest extends TestCase
{
    protected function createParticipant(User $user, EventTicket $ticket, array $attributes = []): EventParticipant
    {
        // Without events: skips the QR code and Discord webhook, which are not under test
        return EventParticipant::withoutEvents(function () use ($user, $ticket, $attributes) {
            $participant = EventParticipant::create([
                'user_id'   => $user->id,
                'event_id'  => $ticket->event_id,
                'ticket_id' => $ticket->id,
            ]);
            $participant->forceFill($attributes)->save();

            return $participant->fresh();
        });
    }

    protected function createSeatingPlan(EventTicket $ticket, array $attributes = []): EventSeatingPlan
    {
        $plan = factory(EventSeatingPlan::class)->make();
        $plan->forceFill(array_merge(['event_id' => $ticket->event_id, 'status' => 'PUBLISHED'], $attributes));
        $plan->save();

        return $plan->fresh();
    }

    protected function pickSeat(User $user, EventSeatingPlan $plan, EventParticipant $participant, string $seat)
    {
        return $this->actingAs($user)
            ->from('/events/' . $participant->event->slug)
            ->post('/events/' . $participant->event->slug . '/seating/' . $plan->slug, [
                'participant_id' => $participant->id,
                'user_id'        => $user->id,
                'seat'           => $seat,
            ]);
    }

    public function testYouCanPickASeatForYourOwnTicket()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();
        $plan = $this->createSeatingPlan($ticket);
        $participant = $this->createParticipant($user, $ticket);

        $this->pickSeat($user, $plan, $participant, 'A1')->assertSessionHas('alert-success');

        $this->assertSame('A1', $participant->fresh()->seat->seat);
    }

    public function testYouCannotSeatSomeoneElsesTicket()
    {
        $owner = $this->createUser();
        $ticket = $this->createEventWithTicket();
        $plan = $this->createSeatingPlan($ticket);
        $participant = $this->createParticipant($owner, $ticket);

        $this->pickSeat($this->createUser(), $plan, $participant, 'A1')
            ->assertSessionHas('alert-danger', 'Event Participant not found');

        $this->assertNull($participant->fresh()->seat);
    }

    public function testYouCannotMoveSomeoneElseOutOfTheirSeat()
    {
        $owner = $this->createUser();
        $ticket = $this->createEventWithTicket();
        $plan = $this->createSeatingPlan($ticket);
        $participant = $this->createParticipant($owner, $ticket);
        $this->pickSeat($owner, $plan, $participant, 'A1');

        $this->pickSeat($this->createUser(), $plan, $participant, 'B2');

        $this->assertSame('A1', $participant->fresh()->seat->seat);
    }

    public function testLockedSeatingCannotBeChanged()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();
        $plan = $this->createSeatingPlan($ticket, ['locked' => true]);
        $participant = $this->createParticipant($user, $ticket);

        $this->pickSeat($user, $plan, $participant, 'A1')
            ->assertSessionHas('alert-danger', 'Seating is locked for this event');

        $this->assertNull($participant->fresh()->seat);
    }

    public function testSeatingPlanFromAnotherEventIsNotFound()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();
        $otherPlan = $this->createSeatingPlan($this->createEventWithTicket());
        $participant = $this->createParticipant($user, $ticket);

        $this->pickSeat($user, $otherPlan, $participant, 'A1')->assertNotFound();
    }

    public function testPickingATakenSeatKeepsYourCurrentSeat()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();
        $plan = $this->createSeatingPlan($ticket);
        $participant = $this->createParticipant($user, $ticket);
        $other = $this->createUser();
        $this->pickSeat($other, $plan, $this->createParticipant($other, $ticket), 'B2');
        $this->pickSeat($user, $plan, $participant, 'A1');

        $this->pickSeat($user, $plan, $participant, 'B2')
            ->assertSessionHas('alert-danger', 'That seat is already taken!');

        $this->assertSame('A1', $participant->fresh()->seat->seat);
    }

    public function testGiftingMakesARandomCode()
    {
        $user = $this->createUser();
        $participant = $this->createParticipant($user, $this->createEventWithTicket());

        $this->actingAs($user)->post('/gift/' . $participant->id)->assertSessionHas('alert-success');

        $participant->refresh();
        $this->assertTrue((bool) $participant->gift);
        $this->assertSame(32, strlen($participant->gift_accepted_url));
    }

    public function testYouCannotGiftSomeoneElsesTicket()
    {
        $participant = $this->createParticipant($this->createUser(), $this->createEventWithTicket());

        $this->actingAs($this->createUser())
            ->post('/gift/' . $participant->id)
            ->assertSessionHas('alert-danger', 'This is not your ticket to gift.');

        $this->assertNull($participant->fresh()->gift_accepted_url);
    }

    public function testYouCannotRevokeSomeoneElsesGift()
    {
        $owner = $this->createUser();
        $participant = $this->createParticipant($owner, $this->createEventWithTicket());
        $this->actingAs($owner)->post('/gift/' . $participant->id);
        $code = $participant->fresh()->gift_accepted_url;

        $this->actingAs($this->createUser())
            ->post('/gift/' . $participant->id . '/revoke')
            ->assertSessionHas('alert-danger', 'This is not your gift to revoke.');

        $this->assertSame($code, $participant->fresh()->gift_accepted_url);
    }

    public function testAcceptingAGiftMovesTheTicket()
    {
        $owner = $this->createUser();
        $friend = $this->createUser();
        $participant = $this->createParticipant($owner, $this->createEventWithTicket());
        $this->actingAs($owner)->post('/gift/' . $participant->id);

        $this->actingAs($friend)
            ->get('/gift/accept?url=' . $participant->fresh()->gift_accepted_url)
            ->assertRedirect('account');

        $this->assertSame($friend->id, $participant->fresh()->user_id);
        $this->assertNull($participant->fresh()->gift_accepted_url);
    }

    public function testAcceptWithoutACodeTakesNothing()
    {
        $owner = $this->createUser();
        $participant = $this->createParticipant($owner, $this->createEventWithTicket());

        $this->actingAs($this->createUser())->get('/gift/accept')
            ->assertSessionHas('alert-danger', 'Redemption code not found.');
        $this->actingAs($this->createUser())->get('/gift/accept?url=')
            ->assertSessionHas('alert-danger', 'Redemption code not found.');

        $this->assertSame($owner->id, $participant->fresh()->user_id);
    }

    public function testSeatInAHiddenSeatingPlanDoesNotBreakThePages()
    {
        // Draft and preview plans are hidden from non-admins, so the seat's plan loads as null
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();
        $plan = $this->createSeatingPlan($ticket);
        $participant = $this->createParticipant($user, $ticket);
        $plan->seats()->where('seat', 'A1')->update(['event_participant_id' => $participant->id]);
        $plan->forceFill(['status' => 'DRAFT'])->save();

        $this->get('/events/' . $ticket->event->slug)
            ->assertOk()
            ->assertSee('A1');

        $this->actingAs($user)->get('/account')->assertOk();
    }
}
