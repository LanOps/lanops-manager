<?php

namespace Tests\Feature;

use App\Models\EventParticipant;
use App\Models\EventSeatingPlan;
use App\Models\EventTicket;
use App\Models\User;
use Mockery;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\OAuth2\User as SocialiteUser;
use Tests\TestCase;

/**
 * The "pick seat" box on the event page: everyone who clicks a seat gets
 * either a way to pick it or a reason they can't, never an empty box.
 */
class SeatPickerTest extends TestCase
{
    protected function createParticipant(User $user, EventTicket $ticket): EventParticipant
    {
        // Without events: skips the QR code and Discord webhook, which are not under test
        return EventParticipant::withoutEvents(fn () => EventParticipant::create([
            'user_id'   => $user->id,
            'event_id'  => $ticket->event_id,
            'ticket_id' => $ticket->id,
        ]));
    }

    protected function createSeatingPlan(EventTicket $ticket): EventSeatingPlan
    {
        $plan = factory(EventSeatingPlan::class)->make();
        $plan->forceFill(['event_id' => $ticket->event_id, 'status' => 'PUBLISHED']);
        $plan->save();

        return $plan->fresh();
    }

    public function testAGuestIsAskedToLogIn()
    {
        $ticket = $this->createEventWithTicket();
        $plan = $this->createSeatingPlan($ticket);

        $this->get('/events/' . $ticket->event->slug)
            ->assertOk()
            ->assertSee('id="pickSeatModal_' . $plan->slug . '"', false)
            ->assertSee('You need to log in to pick a seat.')
            ->assertSee('href="/login"', false)
            ->assertDontSee('Which ticket would you like to seat?');
    }

    public function testSomeoneWithoutATicketIsToldTheyNeedOne()
    {
        $ticket = $this->createEventWithTicket();
        $this->createSeatingPlan($ticket);

        $this->actingAs($this->createUser())
            ->get('/events/' . $ticket->event->slug)
            ->assertOk()
            ->assertSee('You need a ticket for this event to pick a seat.')
            ->assertDontSee('Which ticket would you like to seat?');
    }

    public function testATicketThatIsNotSeatableCannotBePicked()
    {
        $ticket = $this->createEventWithTicket([], ['seatable' => false]);
        $this->createSeatingPlan($ticket);
        $user = $this->createUser();
        $this->createParticipant($user, $ticket);

        $this->actingAs($user)
            ->get('/events/' . $ticket->event->slug)
            ->assertOk()
            ->assertSee('You need a ticket for this event to pick a seat.')
            ->assertDontSee('Yes, pick this seat');
    }

    public function testATicketHolderGetsTheConfirmButton()
    {
        $ticket = $this->createEventWithTicket();
        $plan = $this->createSeatingPlan($ticket);
        $user = $this->createUser();
        $participant = $this->createParticipant($user, $ticket);

        $this->actingAs($user)
            ->get('/events/' . $ticket->event->slug)
            ->assertOk()
            ->assertSee('Which ticket would you like to seat?')
            ->assertSee('value="' . $participant->id . '"', false)
            ->assertSee('Yes, pick this seat')
            ->assertSee('action="' . url('/events/' . $ticket->event->slug . '/seating/' . $plan->slug) . '"', false);
    }

    public function testSteamLoginReturnsToTheGiftLinkThatSentYouToLogIn()
    {
        $user = $this->createUser(['steamid' => '76561198000000077']);
        $provider = Mockery::mock();
        $provider->shouldReceive('redirectUrl')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn((new SocialiteUser())->setRaw([
            'steamid'    => '76561198000000077',
            'avatarfull' => 'https://avatars.example/a.jpg',
        ]));
        Socialite::shouldReceive('driver')->with('steam')->andReturn($provider);

        // Opening a gift link logged out sends you to log in first
        $this->get('/gift/accept?url=somecode')->assertRedirect('/login');

        $this->get('/login/steam?openid.assoc_handle=1234567890&openid.signed=signed&openid.sig=sig')
            ->assertRedirect(url('/gift/accept?url=somecode'));
        $this->assertAuthenticatedAs($user);
    }
}
