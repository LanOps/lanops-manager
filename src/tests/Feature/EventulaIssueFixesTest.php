<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventTicket;
use App\Models\EventTimetable;
use App\Models\EventTimetableData;
use App\Models\Tournament;
use App\Models\TournamentParticipant;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Bugs found by checking the issues on the Lan2Play Eventula fork against
 * our code (November 2026), plus other places with the same pattern.
 */
class EventulaIssueFixesTest extends TestCase
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

    public static function hiddenStatuses(): array
    {
        return [['DRAFT'], ['PREVIEW'], ['PRIVATE']];
    }

    /**
     * Eventula #582: a ticket for an event visitors can't see broke the account page.
     */
    #[DataProvider('hiddenStatuses')]
    public function testAccountPageWorksWithATicketForAnUnpublishedEvent(string $status)
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket(['status' => $status]);
        $this->createParticipant($user, $ticket);
        $name = Event::withoutGlobalScopes()->find($ticket->event_id)->display_name;

        $this->actingAs($user)->get('/account')->assertOk()->assertSee($name);
    }

    public function testUnpublishedEventPageIsStillHiddenFromItsTicketHolders()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket(['status' => 'PRIVATE']);
        $this->createParticipant($user, $ticket);
        $slug = Event::withoutGlobalScopes()->find($ticket->event_id)->slug;

        $this->actingAs($user)->get('/events/' . $slug)->assertNotFound();
    }

    public function testLinkingDiscordWithATicketForAnUnpublishedEvent()
    {
        config([
            'app.discord_bot_url'    => 'https://bot.example.test',
            'app.discord_bot_secret' => 'bot-secret',
        ]);
        Http::fake();
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([
            'status'               => 'PRIVATE',
            'start'                => now()->addWeek(),
            'end'                  => now()->addWeek()->addDays(2),
            'discord_link_enabled' => true,
            'discord_channel_id'   => '1234',
        ]);
        $this->createParticipant($user, $ticket);

        $user->discord_id = '999';
        $user->save();

        Http::assertSent(fn (Request $request) => $request->url() === 'https://bot.example.test/participants/new'
            && $request['channel_id'] === '1234');
    }

    /**
     * Visibility used to be decided once, when each model first loaded.
     */
    public function testVisibilityFollowsWhoIsLoggedInNow()
    {
        $event = Event::withoutGlobalScopes()->find($this->createEventWithTicket(['status' => 'DRAFT'])->event_id);

        $this->assertNull(Event::find($event->id));

        $this->actingAs($this->createUser(['admin' => 1]));
        $this->assertNotNull(Event::find($event->id));

        $this->actingAs($this->createUser());
        $this->assertNull(Event::find($event->id));
    }

    /**
     * Eventula #607: slots without a description were left out.
     */
    public function testTimetableSlotsWithoutADescriptionAreShown()
    {
        $event = $this->createEventWithTicket([
            'status' => 'PUBLISHED',
            'start'  => now()->addDay(),
            'end'    => now()->addDays(3),
        ])->event;
        $timetable = new EventTimetable();
        $timetable->forceFill(['event_id' => $event->id, 'name' => 'Main', 'status' => 'PUBLISHED'])->save();
        $slot = new EventTimetableData();
        $slot->forceFill(['event_timetable_id' => $timetable->id, 'start_time' => $event->start, 'name' => 'Pub Quiz'])->save();

        $this->get('/events/' . $event->slug)->assertOk()->assertSee('Pub Quiz');
        $this->get('/bigscreen/timetable')->assertOk()->assertSee('Pub Quiz');
    }

    /**
     * Eventula #1571: a ticket scanned in at the door could still be passed on.
     */
    public function testASignedInTicketCannotBeGifted()
    {
        $user = $this->createUser();
        $participant = $this->createParticipant($user, $this->createEventWithTicket(), ['signed_in' => true]);

        $this->actingAs($user)->post('/gift/' . $participant->id)->assertSessionHas('alert-danger');

        $this->assertNull($participant->fresh()->gift_accepted_url);
    }

    public function testAGiftCannotBeAcceptedOnceTheTicketIsSignedIn()
    {
        $owner = $this->createUser();
        $participant = $this->createParticipant($owner, $this->createEventWithTicket());
        $this->actingAs($owner)->post('/gift/' . $participant->id)->assertSessionHas('alert-success');
        $code = $participant->fresh()->gift_accepted_url;
        EventParticipant::withoutEvents(fn () => $participant->fresh()->forceFill(['signed_in' => true])->save());

        $this->actingAs($this->createUser())->get('/gift/accept?url=' . $code)
            ->assertRedirect('/account')
            ->assertSessionHas('alert-danger');

        $this->assertSame($owner->id, $participant->fresh()->user_id);
    }

    /**
     * Eventula #1893: the API crashed for an event with no venue.
     */
    public function testApiWorksForAnEventWithNoVenue()
    {
        $event = $this->createEventWithTicket(['status' => 'PUBLISHED', 'event_venue_id' => null])->event;

        $this->getJson('/api/events/' . $event->slug)->assertOk()->assertJsonPath('address.city', null);
    }

    public function testTournamentPageWorksForAPlayerWithNoEventTicket()
    {
        $event = $this->createEventWithTicket(['status' => 'PUBLISHED'])->event;
        $tournament = new Tournament();
        $tournament->name = 'Rocket League ' . uniqid();
        $tournament->team_size = 0; // solo
        $tournament->event_id = $event->id;
        $tournament->save();
        $tournament->status = Tournament::$statusOpen;
        $tournament->save();
        // e.g. their ticket was refunded after they joined
        $player = $this->createUser();
        TournamentParticipant::create(['user_id' => $player->id, 'tournament_id' => $tournament->id]);

        $this->get('/tournaments/' . $tournament->slug)->assertOk()->assertSee($player->username);
    }

    public function testPaymentReviewWorksWithNoUpcomingEvent()
    {
        $ticket = $this->createEventWithTicket([
            'status' => 'PUBLISHED',
            'start'  => now()->subDays(3),
            'end'    => now()->subDay(),
        ]);

        $this->actingAs($this->createUser())->withSession($this->basketFor($ticket))
            ->get('/payment/review/stripe')
            ->assertOk();
    }
}
