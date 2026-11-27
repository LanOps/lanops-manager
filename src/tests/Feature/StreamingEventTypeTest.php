<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventVenue;
use Tests\TestCase;

/**
 * Streaming is an event type next to LAN and Tabletop.
 */
class StreamingEventTypeTest extends TestCase
{
    protected function makeEvent(string $name, string $type): Event
    {
        $ticket = $this->createEventWithTicket([
            'display_name' => $name,
            'nice_name'    => \Illuminate\Support\Str::slug($name),
            'type'         => $type,
            'status'       => 'PUBLISHED',
            'start'        => now()->addWeek(),
            'end'          => now()->addWeek()->addDays(2),
        ]);

        return Event::withoutGlobalScopes()->find($ticket->event_id);
    }

    public function testStreamingIsOfferedOnTheAdminEventPages()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->makeEvent('Stream Admin Test', Event::$typeLan);

        $this->actingAs($admin)->get('/admin/events/' . $event->slug)
            ->assertOk()
            ->assertSee('<option value="STREAMING">Streaming</option>', false);

        // New event form has the Type picker too
        $this->actingAs($admin)->get('/admin/events')
            ->assertOk()
            ->assertSee('name="type"', false)
            ->assertSee('<option value="STREAMING">Streaming</option>', false);
    }

    public function testStatusPickersUseTheSelectStyle()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->makeEvent('Status Arrow Test', Event::$typeLan);

        $html = $this->actingAs($admin)->get('/admin/events/' . $event->slug)->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<select[^>]*class="form-select"[^>]*name="status"|<select[^>]*name="status"[^>]*class="form-select"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*class="form-control"/', $html);
    }

    public function testAnEventCanBeCreatedAndSwitchedToStreaming()
    {
        $admin = $this->createUser(['admin' => 1]);
        $venue = EventVenue::factory()->create();

        $this->actingAs($admin)->post('/admin/events', [
            'event_name' => 'New Stream',
            'desc_short' => 'Short',
            'desc_long'  => 'Long',
            'start_date' => '2027-02-05',
            'start_time' => '16:00',
            'end_date'   => '2027-02-07',
            'end_time'   => '18:00',
            'capacity'   => 100,
            'venue'      => $venue->id,
            'type'       => 'STREAMING',
        ])->assertSessionHasNoErrors();
        $this->assertSame('STREAMING', Event::withoutGlobalScopes()->where('display_name', 'New Stream')->value('type'));

        $event = $this->makeEvent('Switch To Stream', Event::$typeLan);
        $this->actingAs($admin)->post('/admin/events/' . $event->slug, ['type' => 'STREAMING'])
            ->assertSessionHasNoErrors();
        $this->assertSame('STREAMING', $event->fresh()->type);
    }

    public function testUnknownTypesAreRejected()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->makeEvent('Bad Type Test', Event::$typeLan);

        $this->actingAs($admin)->post('/admin/events/' . $event->slug, ['type' => 'BOGUS'])
            ->assertSessionHasErrors('type');
        $this->assertSame('LAN', $event->fresh()->type);
    }

    public function testTheHomePageShowsTheNextStreamAfterLanAndTabletop()
    {
        $stream   = $this->makeEvent('Clickable Stream', Event::$typeStreaming);
        $tabletop = $this->makeEvent('Clickable Tabletop', Event::$typeTabletop);
        $lan      = $this->makeEvent('Clickable LAN', Event::$typeLan);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Next LAN', 'Clickable LAN', 'Next Tabletop', 'Clickable Tabletop', 'Next Stream', 'Clickable Stream'])
            ->assertSee('<h2 class="hero-event-name"><a href="/events/' . $stream->slug . '" class="hero-event-link">Clickable Stream</a></h2>', false)
            ->assertSee('event-card-badge event-card-badge--stream', false)
            ->assertSee('col-12 col-md-6 col-lg-4', false);
    }

    public function testStreamingBadgeOnPublicEventPages()
    {
        $stream = $this->makeEvent('Badge Stream', Event::$typeStreaming);

        $this->get('/events')
            ->assertOk()
            ->assertSee('event-card-badge event-card-badge--stream', false);

        $this->get('/events/' . $stream->slug)
            ->assertOk()
            ->assertSee('event-detail-badge event-detail-badge--stream', false);
    }
}
