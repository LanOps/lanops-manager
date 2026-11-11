<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventTicket;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Admins changing a user's email, the "Show my ticket" banner during an event,
 * and past tickets folded away on the account page.
 */
class AccountTicketsTest extends TestCase
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

    protected function liveTicket(): EventTicket
    {
        return $this->createEventWithTicket([
            'status' => 'PUBLISHED',
            'start'  => now()->subHours(3),
            'end'    => now()->addDay(),
        ]);
    }

    protected function eventName(EventTicket $ticket): string
    {
        return Event::withoutGlobalScopes()->find($ticket->event_id)->display_name;
    }

    public function testAdminCanChangeAUsersEmailAndSendAVerificationLink()
    {
        Notification::fake();
        $admin = $this->createUser(['admin' => 1]);
        $user = $this->createUser(['email_verified_at' => now()]);

        $this->actingAs($admin)->from('/admin/users/' . $user->id)
            ->post('/admin/users/' . $user->id . '/email', ['email' => 'fixed@example.test', 'send_verification' => '1'])
            ->assertRedirect('/admin/users/' . $user->id)
            ->assertSessionHas('alert-success');

        $user->refresh();
        $this->assertSame('fixed@example.test', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testAdminCanChangeAUsersEmailAndMarkItVerified()
    {
        Notification::fake();
        $admin = $this->createUser(['admin' => 1]);
        $user = $this->createUser(['email_verified_at' => null]);

        $this->actingAs($admin)->post('/admin/users/' . $user->id . '/email', ['email' => 'checked@example.test'])
            ->assertSessionHas('alert-success');

        $user->refresh();
        $this->assertSame('checked@example.test', $user->email);
        $this->assertNotNull($user->email_verified_at);
        Notification::assertNothingSent();
    }

    public function testAdminCannotGiveAUserSomeoneElsesEmail()
    {
        $admin = $this->createUser(['admin' => 1]);
        $taken = $this->createUser();
        $user = $this->createUser();
        $original = $user->email;

        $this->actingAs($admin)->post('/admin/users/' . $user->id . '/email', ['email' => $taken->email])
            ->assertSessionHasErrors('email');

        $this->assertSame($original, $user->fresh()->email);
    }

    public function testNonAdminsCannotChangeEmails()
    {
        $user = $this->createUser();
        $original = $user->email;

        $this->actingAs($this->createUser())->post('/admin/users/' . $user->id . '/email', ['email' => 'mine@example.test']);

        $this->assertSame($original, $user->fresh()->email);
    }

    public function testShowMyTicketBannerDuringAnEvent()
    {
        $user = $this->createUser();
        $participant = $this->createParticipant($user, $this->liveTicket());

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Show my ticket')
            ->assertSee('/account/tickets/' . $participant->id, false);
    }

    public function testNoBannerOnceSignedIn()
    {
        $user = $this->createUser();
        $this->createParticipant($user, $this->liveTicket(), ['signed_in' => true]);

        $this->actingAs($user)->get('/')->assertOk()->assertDontSee('Show my ticket');
    }

    public function testNoBannerBeforeTheEventStarts()
    {
        $user = $this->createUser();
        $this->createParticipant($user, $this->createEventWithTicket([
            'status' => 'PUBLISHED',
            'start'  => now()->addWeek(),
            'end'    => now()->addWeek()->addDays(2),
        ]));

        $this->actingAs($user)->get('/')->assertOk()->assertDontSee('Show my ticket');
    }

    public function testNoBannerForATicketWaitingToBeTakenAsAGift()
    {
        $user = $this->createUser();
        $this->createParticipant($user, $this->liveTicket(), ['gift' => true, 'gift_accepted' => false]);

        $this->actingAs($user)->get('/')->assertOk()->assertDontSee('Show my ticket');
    }

    public function testTicketPageShowsYourTicket()
    {
        $user = $this->createUser();
        $ticket = $this->liveTicket();
        $participant = $this->createParticipant($user, $ticket);

        $this->actingAs($user)->get('/account/tickets/' . $participant->id)
            ->assertOk()
            ->assertSee($this->eventName($ticket))
            ->assertSee($ticket->name)
            ->assertDontSee('Show my ticket');
    }

    public function testTicketPageIsOnlyForTheTicketHolder()
    {
        $participant = $this->createParticipant($this->createUser(), $this->liveTicket());

        $this->actingAs($this->createUser())->get('/account/tickets/' . $participant->id)->assertNotFound();
    }

    public function testPastTicketsAreFoldedAwayOnTheAccountPage()
    {
        $user = $this->createUser();
        $upcoming = $this->createEventWithTicket([
            'status' => 'PUBLISHED',
            'start'  => now()->addWeek(),
            'end'    => now()->addWeek()->addDays(2),
        ]);
        $past = $this->createEventWithTicket([
            'status' => 'PUBLISHED',
            'start'  => now()->subMonth(),
            'end'    => now()->subMonth()->addDays(2),
        ]);
        $this->createParticipant($user, $upcoming);
        $this->createParticipant($user, $past);

        $page = $this->actingAs($user)->get('/account')->assertOk();

        $page->assertSee("Past events' tickets (1)", false)
            ->assertSee('class="collapse " id="pastTickets"', false)
            ->assertSee($this->eventName($upcoming))
            ->assertSee($this->eventName($past))
            ->assertDontSee('Ticket already claimed at previous event!');

        // Paging through past tickets keeps the list open
        $this->actingAs($user)->get('/account?ti=1')->assertOk()
            ->assertSee('class="collapse show" id="pastTickets"', false);
    }
}
