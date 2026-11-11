<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventTicket;
use App\Models\User;
use Tests\TestCase;

/**
 * Private events: hidden from the website, open to admins, to ticket holders,
 * and to anyone who has the event's secret link.
 */
class PrivateEventsTest extends TestCase
{
    protected function privateEvent(array $attributes = []): Event
    {
        $ticket = $this->createEventWithTicket(array_merge([
            'status'       => 'PRIVATE',
            'display_name' => 'Secret Squirrel LAN',
            'start'        => now()->addWeek(),
            'end'          => now()->addWeek()->addDays(2),
        ], $attributes));

        return Event::withoutGlobalScopes()->find($ticket->event_id);
    }

    protected function ticketFor(Event $event): EventTicket
    {
        return EventTicket::where('event_id', $event->id)->first();
    }

    public function testAPrivateEventGetsASecretLinkWhenItIsCreated()
    {
        $event = $this->privateEvent();

        $this->assertSame(32, strlen($event->private_key));
        $this->assertSame(url('/e/' . $event->private_key), $event->getPrivateUrl());
        // Never sent out with the event's data
        $this->assertArrayNotHasKey('private_key', $event->toArray());
    }

    public function testMakingAnEventPrivateFromTheAdminPageGivesItALink()
    {
        $event = Event::withoutGlobalScopes()->find($this->createEventWithTicket()->event_id);
        $this->assertNull($event->private_key);
        $admin = $this->createUser(['admin' => 1]);

        $this->actingAs($admin)->post('/admin/events/' . $event->slug, ['status' => 'private'])->assertRedirect();

        $event = Event::withoutGlobalScopes()->find($event->id);
        $this->assertSame('PRIVATE', $event->status);
        $this->assertNotEmpty($event->private_key);
        $this->actingAs($admin)->get('/admin/events/' . $event->slug)
            ->assertOk()
            ->assertSee('Private Link')
            ->assertSee($event->getPrivateUrl());
    }

    public function testPrivateEventsAreHiddenFromVisitorsWithoutTheLink()
    {
        $event = $this->privateEvent();

        $this->get('/events/' . $event->slug)->assertNotFound();
        $this->actingAs($this->createUser())->get('/events/' . $event->slug)->assertNotFound();
    }

    public function testPrivateEventsAreLeftOutOfEveryListing()
    {
        $event = $this->privateEvent();

        // Even after opening the link, and even for admins
        $this->get('/e/' . $event->private_key);
        foreach ([null, $this->createUser(['admin' => 1])] as $user) {
            if ($user) {
                $this->actingAs($user);
            }
            $this->get('/')->assertOk()->assertDontSee('Secret Squirrel LAN');
            $this->get('/events')->assertOk()->assertDontSee('Secret Squirrel LAN');
            $this->get('/gallery')->assertOk()->assertDontSee('Secret Squirrel LAN');
        }
        $this->get('/sitemap')->assertOk()->assertDontSee($event->slug);
        $this->getJson('/api/events')->assertOk()->assertDontSee('Secret Squirrel LAN');
        $this->getJson('/api/events/upcoming')->assertOk()->assertDontSee('Secret Squirrel LAN');
        $this->getJson('/api/events/' . $event->slug)->assertNotFound();
    }

    public function testTheSecretLinkOpensTheEvent()
    {
        $event = $this->privateEvent();

        $this->get('/e/' . $event->private_key)->assertRedirect('/events/' . $event->slug);

        $this->get('/events/' . $event->slug)
            ->assertOk()
            ->assertSee('Secret Squirrel LAN')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function testTheLinkCarriesOnWorkingAfterLoggingIn()
    {
        $event = $this->privateEvent();
        $this->get('/e/' . $event->private_key);

        $this->actingAs($this->createUser())->get('/events/' . $event->slug)->assertOk();
    }

    public function testAWrongLinkIsNotFound()
    {
        $event = $this->privateEvent();

        $this->get('/e/' . strtoupper($event->private_key))->assertNotFound();
        $this->get('/e/nope')->assertNotFound();
        $this->get('/events/' . $event->slug)->assertNotFound();
    }

    public function testPublishedEventsHaveNoSecretLink()
    {
        $event = Event::withoutGlobalScopes()->find($this->createEventWithTicket()->event_id);
        $event->private_key = 'abcdefghijklmnopqrstuvwxyz123456';
        $event->save();

        $this->assertNull($event->getPrivateUrl());
        $this->get('/e/abcdefghijklmnopqrstuvwxyz123456')->assertNotFound();
    }

    public function testTicketsCanBeBoughtThroughTheLink()
    {
        $event = $this->privateEvent();
        $ticket = $this->ticketFor($event);
        $user = $this->createUser();

        // Without the link the ticket can't be bought
        $this->actingAs($user)
            ->post('/tickets/purchase/' . $ticket->id, ['quantity' => 1])
            ->assertNotFound();

        $this->get('/e/' . $event->private_key);
        $this->actingAs($user)
            ->post('/tickets/purchase/' . $ticket->id, ['quantity' => 1])
            ->assertRedirect('/payment/checkout');
        $this->get('/payment/checkout')->assertOk()->assertSee($ticket->name);
    }

    public function testTicketHoldersSeeTheEventWithoutTheLink()
    {
        $event = $this->privateEvent();
        $user = $this->createUser();
        EventParticipant::withoutEvents(fn () => EventParticipant::create([
            'user_id'   => $user->id,
            'event_id'  => $event->id,
            'ticket_id' => $this->ticketFor($event)->id,
        ]));

        $this->actingAs($user)->get('/events/' . $event->slug)->assertOk()->assertSee('Secret Squirrel LAN');
        $this->actingAs($user)->get('/account')->assertOk()->assertSee('Secret Squirrel LAN');
        // But it still stays out of the lists
        $this->actingAs($user)->get('/events')->assertOk()->assertDontSee('Secret Squirrel LAN');

        // Someone else's ticket doesn't let you in
        $this->actingAs($this->createUser())->get('/events/' . $event->slug)->assertNotFound();
    }

    public function testAdminsSeePrivateEvents()
    {
        $event = $this->privateEvent();

        $this->actingAs($this->createUser(['admin' => 1]))->get('/events/' . $event->slug)->assertOk();
    }

    public function testMakingANewLinkShutsOutTheOldOne()
    {
        $event = $this->privateEvent();
        $oldKey = $event->private_key;
        $this->get('/e/' . $oldKey);
        $this->get('/events/' . $event->slug)->assertOk();

        $admin = $this->createUser(['admin' => 1]);
        $this->actingAs($admin)
            ->post('/admin/events/' . $event->slug . '/private-link')
            ->assertRedirect('/admin/events/' . $event->slug)
            ->assertSessionHas('alert-success');
        $event = Event::withoutGlobalScopes()->find($event->id);
        $this->assertNotSame($oldKey, $event->private_key);

        // Same session, back as a visitor: the old link no longer counts
        $this->actingAs($this->createUser());
        $this->get('/events/' . $event->slug)->assertNotFound();
        $this->get('/e/' . $oldKey)->assertNotFound();
        $this->get('/e/' . $event->private_key)->assertRedirect('/events/' . $event->slug);
        $this->get('/events/' . $event->slug)->assertOk();
    }

    public function testOnlyPrivateEventsCanHaveANewLinkMade()
    {
        $event = Event::withoutGlobalScopes()->find($this->createEventWithTicket()->event_id);

        $this->actingAs($this->createUser(['admin' => 1]))
            ->post('/admin/events/' . $event->slug . '/private-link')
            ->assertSessionHas('alert-danger');
        $this->assertNull($event->fresh()->private_key);
    }

    public function testVisitorsCannotMakeANewLink()
    {
        $event = $this->privateEvent();

        $this->actingAs($this->createUser())->post('/admin/events/' . $event->slug . '/private-link');

        $this->assertSame($event->private_key, Event::withoutGlobalScopes()->find($event->id)->private_key);
    }

    public function testOpeningOneLinkDoesNotOpenOtherPrivateEvents()
    {
        $first = $this->privateEvent();
        $second = $this->privateEvent(['display_name' => 'Other Hidden LAN']);

        $this->get('/e/' . $first->private_key);

        $this->get('/events/' . $first->slug)->assertOk();
        $this->get('/events/' . $second->slug)->assertNotFound();
    }
}
