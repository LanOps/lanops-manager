<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventTicket;
use App\Models\EventVenue;
use Tests\TestCase;

/**
 * Admin event and ticket forms use native date inputs, which send Y-m-d.
 */
class AdminDatesTest extends TestCase
{
    public function testEventIsCreatedFromNativeDateInputs()
    {
        $admin = $this->createUser(['admin' => 1]);
        $venue = factory(EventVenue::class)->create();

        $this->actingAs($admin)->post('/admin/events', [
            'event_name' => 'Date Test LAN',
            'desc_short' => 'Short',
            'desc_long' => 'Long',
            'start_date' => '2026-11-15',
            'start_time' => '16:00',
            'end_date' => '2026-11-17',
            'end_time' => '18:00',
            'capacity' => 40,
            'venue' => $venue->id,
        ])->assertSessionHasNoErrors();

        $event = Event::where('display_name', 'Date Test LAN')->firstOrFail();
        $this->assertSame('2026-11-15 16:00:00', $event->start);
        $this->assertSame('2026-11-17 18:00:00', $event->end);
    }

    public function testOldDateFormatIsRejected()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->createEventWithTicket()->event;

        $this->actingAs($admin)->post('/admin/events/' . $event->slug, ['start_date' => '11/15/2026'])
            ->assertSessionHasErrors('start_date');
    }

    public function testEventDatesAreUpdatedAndShownInTheForm()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->createEventWithTicket(['start' => '2026-11-15 16:00:00', 'end' => '2026-11-17 18:00:00'])->event;

        $this->actingAs($admin)->post('/admin/events/' . $event->slug, [
            'start_date' => '2026-11-14',
            'end_time' => '20:30',
        ])->assertSessionHasNoErrors();

        $event->refresh();
        $this->assertSame('2026-11-14 16:00:00', $event->start);
        $this->assertSame('2026-11-17 20:30:00', $event->end);

        $this->actingAs($admin)->get('/admin/events/' . $event->slug)
            ->assertOk()
            ->assertSee('type="date" name="start_date" id="start_date" value="2026-11-14"', false);
    }

    public function testTicketSaleDatesFromNativeDateInputs()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->createEventWithTicket()->event;

        $this->actingAs($admin)->post('/admin/events/' . $event->slug . '/tickets', [
            'name' => 'Date Test Ticket',
            'price' => 10,
            'type' => 'participant',
            'quantity' => 0,
            'sale_start_date' => '2026-11-01',
            'sale_start_time' => '09:00',
            'sale_end_date' => '2026-11-14',
            'sale_end_time' => '23:59',
        ])->assertSessionHasNoErrors();

        $ticket = EventTicket::where('name', 'Date Test Ticket')->firstOrFail();
        $this->assertSame('2026-11-01 09:00:00', $ticket->sale_start);
        $this->assertSame('2026-11-14 23:59:00', $ticket->sale_end);

        $this->actingAs($admin)->get('/admin/events/' . $event->slug . '/tickets/' . $ticket->id)
            ->assertOk()
            ->assertSee('type="date" name="sale_start_date" id="sale_start_date" value="2026-11-01"', false);
    }
}
