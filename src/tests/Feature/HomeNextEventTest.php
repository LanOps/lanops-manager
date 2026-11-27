<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The home page "Next LAN / Next Tabletop / Next Stream" slots only show events that have not started yet.
 */
class HomeNextEventTest extends TestCase
{
    protected function makeEvent(string $name, string $type, $start, $end): Event
    {
        $ticket = $this->createEventWithTicket([
            'display_name' => $name,
            'nice_name'    => Str::slug($name),
            'type'         => $type,
            'status'       => 'PUBLISHED',
            'start'        => $start,
            'end'          => $end,
        ]);

        return Event::withoutGlobalScopes()->find($ticket->event_id);
    }

    public function testALiveEventMovesEachSlotOnToTheNextOne()
    {
        $types = [Event::$typeLan => 'Lan', Event::$typeTabletop => 'Tabletop', Event::$typeStreaming => 'Stream'];
        foreach ($types as $type => $label) {
            $this->makeEvent("Live $label Event", $type, now()->subHours(3), now()->addDays(2));
            $this->makeEvent("Later $label Event", $type, now()->addWeeks(5), now()->addWeeks(5)->addDays(2));
            $this->makeEvent("Soon $label Event", $type, now()->addWeek(), now()->addWeek()->addDays(2));
        }

        $response = $this->get('/')->assertOk();
        $nextEvents = $response->viewData('nextEvents');

        foreach ($types as $type => $label) {
            $this->assertSame("Soon $label Event", $nextEvents[$type]->display_name);
        }
        $hero = Str::before(Str::after($response->getContent(), 'hero-events-grid'), 'hero-bio');
        $this->assertStringNotContainsString('Live Lan Event', $hero);
        $this->assertStringContainsString('Soon Lan Event', $hero);
    }

    public function testATypeWithOnlyALiveEventIsLeftOut()
    {
        $this->makeEvent('Only Live Lan', Event::$typeLan, now()->subDay(), now()->addDay());

        $nextEvents = $this->get('/')->assertOk()->viewData('nextEvents');
        $this->assertArrayNotHasKey(Event::$typeLan, $nextEvents);
    }

    public function testNavigationKeepsTheLiveEventAndUpcomingListSkipsIt()
    {
        $this->makeEvent('Nav Live Lan', Event::$typeLan, now()->subHour(), now()->addDays(2));
        $this->makeEvent('Nav Next Lan', Event::$typeLan, now()->addWeek(), now()->addWeek()->addDays(2));

        $html = $this->get('/events')->assertOk()->getContent();
        $this->assertStringContainsString('On Now: Nav Live Lan', $html);

        $upcoming = Str::before(Str::after($html, '>Upcoming Events</a>'), '</ul>');
        $this->assertStringContainsString('Nav Next Lan', $upcoming);
        $this->assertStringNotContainsString('Nav Live Lan', $upcoming);
    }

    public function testNavigationSaysNextEventBeforeItStarts()
    {
        $this->makeEvent('Nav Future Lan', Event::$typeLan, now()->addWeek(), now()->addWeek()->addDays(2));

        $this->get('/events')->assertOk()->assertSee('Next Event: Nav Future Lan');
    }

    public function testALiveEventGetsAnOnNowBarLinkingToIt()
    {
        $live = $this->makeEvent('Bar Live Lan', Event::$typeLan, now()->subHours(2), now()->addDays(2));
        $this->makeEvent('Bar Next Lan', Event::$typeLan, now()->addWeek(), now()->addWeek()->addDays(2));

        $html = $this->get('/')->assertOk()->getContent();
        $bar = Str::before(Str::after($html, 'class="live-event-bar"'), 'hero-section');
        $this->assertStringContainsString('Bar Live Lan', $bar);
        $this->assertStringContainsString('href="/events/' . $live->slug . '"', $bar);
        $this->assertStringContainsString('href="/events/' . $live->slug . '#timetable"', $bar);
        $this->assertStringNotContainsString('Bar Next Lan', $bar);
    }

    public function testNoOnNowBarWhenNothingIsOn()
    {
        $this->makeEvent('Quiet Next Lan', Event::$typeLan, now()->addWeek(), now()->addWeek()->addDays(2));

        $this->get('/')->assertOk()->assertDontSee('class="live-event-bar"', false);
    }
}
