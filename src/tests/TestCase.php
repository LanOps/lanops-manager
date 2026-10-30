<?php

namespace Tests;

use App\Models\Event;
use App\Models\EventTicket;
use App\Models\EventVenue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        \App\Libraries\Helpers::flushSteamProfiles();
        Storage::fake(config('media-library.disk_name'));
    }

    /**
     * Switch user like a fresh login would: AuthenticateSession keeps the
     * signed-in user's password hash in the session, and would sign out a
     * different user acting on that same session.
     */
    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        if ($this->app->bound('session.store')) {
            $this->app['session.store']->forget('password_hash_web');
        }

        return parent::actingAs($user, $guard);
    }

    /**
     * Create a verified, unbanned user.
     */
    protected function createUser(array $attributes = []): User
    {
        $user = factory(User::class)->make();
        $user->forceFill(array_merge([
            'email_verified_at' => now(),
            'referral_code'     => User::generateReferralCode(),
        ], $attributes));
        $user->save();

        return $user->fresh();
    }

    /**
     * Create a published upcoming event, at a venue, with one ticket on sale.
     */
    protected function createEventWithTicket(array $eventAttributes = [], array $ticketAttributes = []): EventTicket
    {
        $venue = factory(EventVenue::class)->create();

        $event = factory(Event::class)->make();
        $event->forceFill(array_merge(['capacity' => 100, 'event_venue_id' => $venue->id], $eventAttributes));
        $event->save();

        $ticket = factory(EventTicket::class)->make();
        $ticket->forceFill(array_merge(['event_id' => $event->id], $ticketAttributes));
        $ticket->save();

        return $ticket->fresh();
    }

    /**
     * Put a ticket basket into the session, as TicketsController::purchase does.
     */
    protected function basketFor(EventTicket $ticket, int $quantity = 1): array
    {
        return [
            config('app.basket_name') => [
                'tickets'           => [$ticket->id => $quantity],
                'referral_discount' => false,
            ],
        ];
    }
}
