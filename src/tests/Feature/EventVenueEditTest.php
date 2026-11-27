<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventVenue;
use Tests\TestCase;

/**
 * Admins can change (or clear) the venue on any event, published ones included.
 */
class EventVenueEditTest extends TestCase
{
    protected function publishedEvent(): Event
    {
        $ticket = $this->createEventWithTicket([
            'display_name' => 'Venue Move Test',
            'nice_name'    => 'venue-move-test-' . uniqid(),
            'status'       => 'PUBLISHED',
            'start'        => now()->addWeek(),
            'end'          => now()->addWeek()->addDays(2),
        ]);

        return Event::withoutGlobalScopes()->find($ticket->event_id);
    }

    public function testSettingsFormHasAVenuePicker()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->publishedEvent();
        $other = EventVenue::factory()->create(['display_name' => 'The New Hall']);

        $this->actingAs($admin)->get('/admin/events/' . $event->slug)
            ->assertOk()
            ->assertSee('name="venue"', false)
            ->assertSee('<option value="' . $other->id . '">The New Hall</option>', false)
            ->assertSee('<option value="' . $event->event_venue_id . '" selected="selected">', false);
    }

    public function testVenueCanBeChangedOnAPublishedEvent()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->publishedEvent();
        $other = EventVenue::factory()->create(['display_name' => 'The New Hall']);

        $this->actingAs($admin)->post('/admin/events/' . $event->slug, ['venue' => $other->id])
            ->assertSessionHasNoErrors();

        $event = $event->fresh();
        $this->assertSame($other->id, (int) $event->event_venue_id);
        $this->assertSame('PUBLISHED', $event->status);

        $this->get('/events/' . $event->slug)->assertOk()->assertSee('The New Hall');
    }

    public function testOtherSettingsSavesLeaveTheVenueAlone()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->publishedEvent();
        $venueId = $event->event_venue_id;

        $this->actingAs($admin)->post('/admin/events/' . $event->slug, ['capacity' => 80])
            ->assertSessionHasNoErrors();
        $this->assertEquals($venueId, $event->fresh()->event_venue_id);
    }

    public function testVenueCanBeClearedAndThePublicPageStillWorks()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->publishedEvent();

        $this->actingAs($admin)->post('/admin/events/' . $event->slug, ['venue' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($event->fresh()->event_venue_id);

        $this->get('/events/' . $event->slug)->assertOk()->assertDontSee('Venue Information');
        $this->actingAs($admin)->get('/admin/events/' . $event->slug)->assertOk();
    }

    public function testUnknownVenueIsRejected()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->publishedEvent();

        $this->actingAs($admin)->post('/admin/events/' . $event->slug, ['venue' => 999999])
            ->assertSessionHasErrors('venue');
    }

    public function testNewEventCanBeCreatedWithNoVenue()
    {
        $admin = $this->createUser(['admin' => 1]);

        $this->actingAs($admin)->post('/admin/events', [
            'event_name' => 'No Venue Stream',
            'desc_short' => 'Short',
            'desc_long'  => 'Long',
            'start_date' => '2027-02-05',
            'start_time' => '16:00',
            'end_date'   => '2027-02-07',
            'end_time'   => '18:00',
            'capacity'   => 100,
            'venue'      => '',
            'type'       => 'STREAMING',
        ])->assertSessionHasNoErrors();

        $this->assertNull(Event::withoutGlobalScopes()->where('display_name', 'No Venue Stream')->value('event_venue_id'));
    }
}
