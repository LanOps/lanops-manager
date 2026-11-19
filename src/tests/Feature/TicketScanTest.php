<?php

namespace Tests\Feature;

use App\Models\EventParticipant;
use App\Models\EventSeating;
use App\Models\EventSeatingPlan;
use App\Models\EventTicket;
use App\Models\User;
use Tests\TestCase;

/**
 * What a ticket's QR code opens: a door sign-in page for admins, full details
 * for the ticket's owner, and only event / ticket type / valid for anyone else.
 */
class TicketScanTest extends TestCase
{
    private User $holder;
    private EventTicket $ticket;
    private EventParticipant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->holder = $this->createUser(['username' => 'DoorTester', 'firstname' => 'Alex', 'surname' => 'Example']);
        $this->ticket = $this->createEventWithTicket(
            ['status' => 'PUBLISHED', 'start' => now()->subHour(), 'end' => now()->addDay()],
            ['name' => 'Weekend Pass']
        );
        $this->participant = EventParticipant::withoutEvents(fn () => EventParticipant::create([
            'user_id'   => $this->holder->id,
            'event_id'  => $this->ticket->event_id,
            'ticket_id' => $this->ticket->id,
        ]));
        $this->participant->forceFill(['qr_token' => 'abcDEF123'])->saveQuietly();

        $plan = EventSeatingPlan::factory()->make();
        $plan->forceFill(['event_id' => $this->ticket->event_id, 'status' => 'PUBLISHED'])->save();
        EventSeating::where('event_seating_plan_id', $plan->id)->where('seat', 'B7')->firstOrFail()
            ->forceFill(['event_participant_id' => $this->participant->id])->save();
    }

    private function scan()
    {
        return $this->get('/tickets/retrieve/abcDEF123');
    }

    public function testAGuestOnlySeesTheEventTicketTypeAndThatItIsValid(): void
    {
        $this->scan()
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee($this->ticket->event->display_name)
            ->assertSee('Weekend Pass')
            ->assertSee('This is a valid ticket')
            ->assertSee('Log in')
            ->assertDontSee('DoorTester')
            ->assertDontSee('Alex Example')
            ->assertDontSee('B7')
            ->assertDontSee('/signin');

        // Logging in from here comes back to this ticket
        $this->assertSame(url('/tickets/retrieve/abcDEF123'), session('url.intended'));
    }

    public function testSomeoneElseLoggedInSeesNoPersonalDetails(): void
    {
        $this->actingAs($this->createUser());

        $this->scan()
            ->assertOk()
            ->assertSee('belongs to someone else')
            ->assertDontSee('DoorTester')
            ->assertDontSee('B7')
            ->assertDontSee('/signin');
    }

    public function testABannedUserIsTreatedLikeAGuest(): void
    {
        $this->actingAs($this->createUser(['admin' => 1, 'banned' => 1]));

        $this->scan()->assertOk()->assertDontSee('DoorTester')->assertDontSee('/signin');
    }

    public function testTheOwnerSeesTheirFullTicket(): void
    {
        $this->actingAs($this->holder);

        $this->scan()
            ->assertOk()
            ->assertSee('Your ticket')
            ->assertSee('DoorTester')
            ->assertSee('Alex Example')
            ->assertSee('B7')
            ->assertSee('Weekend Pass')
            ->assertSee('/account/tickets/' . $this->participant->id)
            ->assertDontSee('/signin');
    }

    public function testAnAdminCanSignTheHolderInFromTheScanPage(): void
    {
        $admin = $this->createUser(['admin' => 1]);
        $signIn = '/admin/events/' . $this->ticket->event->slug . '/participants/' . $this->participant->id . '/signin';

        $this->actingAs($admin)->scan()
            ->assertOk()
            ->assertSee('Valid ticket')
            ->assertSee('DoorTester')
            ->assertSee('Alex Example')
            ->assertSee('B7')
            ->assertSee($signIn);

        $this->from('/tickets/retrieve/abcDEF123')->post($signIn)
            ->assertRedirect('/tickets/retrieve/abcDEF123')
            ->assertSessionHas('alert-success');
        $this->assertTrue((bool) $this->participant->fresh()->signed_in);

        $this->scan()->assertOk()->assertSee('Already signed in')->assertDontSee($signIn);
    }

    public function testNonAdminsCannotSignIn(): void
    {
        $signIn = '/admin/events/' . $this->ticket->event->slug . '/participants/' . $this->participant->id . '/signin';

        $this->actingAs($this->holder)->post($signIn);

        $this->assertFalse((bool) $this->participant->fresh()->signed_in);
    }

    public function testAnAdminIsWarnedAboutATicketForAnEventNotStartedYet(): void
    {
        $this->ticket->event->forceFill(['start' => now()->addWeek(), 'end' => now()->addWeek()->addDay()])->save();

        $this->actingAs($this->createUser(['admin' => 1]))->scan()
            ->assertOk()->assertSee("hasn't started yet", false);
    }

    public function testHiddenEventsTicketsStillScan(): void
    {
        $this->ticket->event->forceFill(['status' => 'DRAFT'])->save();

        $this->scan()->assertOk()->assertSee('Weekend Pass');
    }

    public function testUnknownTokensAndIdsAreNotFound(): void
    {
        $this->get('/tickets/retrieve/nope')->assertNotFound();
        $this->get('/tickets/retrieve/' . $this->participant->id)->assertNotFound();
    }
}
