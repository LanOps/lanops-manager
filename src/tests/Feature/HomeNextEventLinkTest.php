<?php

namespace Tests\Feature;

use App\Models\Event;
use Tests\TestCase;

/**
 * The event name in the home page hero links to the event page.
 */
class HomeNextEventLinkTest extends TestCase
{
    protected function makeEvent(string $name, string $type, string $status = 'PUBLISHED'): Event
    {
        $ticket = $this->createEventWithTicket([
            'display_name' => $name,
            'nice_name'    => \Illuminate\Support\Str::slug($name),
            'type'         => $type,
            'status'       => $status,
            'start'        => now()->addWeek(),
            'end'          => now()->addWeek()->addDays(2),
        ]);

        return Event::withoutGlobalScopes()->find($ticket->event_id);
    }

    public function testTheNextLanNameLinksToTheEventPage()
    {
        $event = $this->makeEvent('Clickable LAN', Event::$typeLan);

        $this->get('/')
            ->assertOk()
            ->assertSee('<h2 class="hero-event-name"><a href="/events/' . $event->slug . '" class="hero-event-link">Clickable LAN</a></h2>', false)
            // The ticket button is still there
            ->assertSee('href="/events/' . $event->slug . '#tickets"', false);
    }

    public function testTheNextTabletopNameLinksToTheEventPage()
    {
        $event = $this->makeEvent('Clickable Tabletop', Event::$typeTabletop);

        $this->get('/')
            ->assertOk()
            ->assertSee('<h2 class="hero-event-name"><a href="/events/' . $event->slug . '" class="hero-event-link">Clickable Tabletop</a></h2>', false);
    }

    public function testPrivateEventsAreNotLinkedFromTheHomePage()
    {
        $event = $this->makeEvent('Hidden LAN', Event::$typeLan, 'PRIVATE');

        foreach ([null, $this->createUser(['admin' => 1])] as $user) {
            if ($user) {
                $this->actingAs($user);
            }
            $this->get('/')
                ->assertOk()
                ->assertDontSee('href="/events/' . $event->slug . '"', false)
                ->assertDontSee('class="hero-event-link">Hidden LAN', false)
                ->assertSee('Coming Soon');
        }
    }
}
