<?php

namespace Tests\Feature;

use App\Models\Event;
use Tests\TestCase;

/**
 * /admin/events lists every event, newest start date first.
 */
class AdminEventsListTest extends TestCase
{
    protected function makeEvent(string $name, string $start, string $status = 'PUBLISHED', ?string $createdAt = null): Event
    {
        $ticket = $this->createEventWithTicket([
            'display_name' => $name,
            'nice_name'    => \Illuminate\Support\Str::slug($name),
            'status'       => $status,
            'start'        => $start,
            'end'          => \Carbon\Carbon::parse($start)->addDays(2),
        ]);
        $event = Event::withoutGlobalScopes()->find($ticket->event_id);

        if ($createdAt) {
            $event->forceFill(['created_at' => $createdAt])->saveQuietly();
        }

        return $event;
    }

    public function testEventsAreOrderedByStartDateNewestFirst()
    {
        // Created in a different order from their start dates
        $this->makeEvent('Middle LAN', '2025-06-01 16:00:00', 'PUBLISHED', '2026-01-01 00:00:00');
        $this->makeEvent('Newest LAN', '2026-11-15 16:00:00', 'PUBLISHED', '2024-01-01 00:00:00');
        $this->makeEvent('Oldest LAN', '2024-02-01 16:00:00', 'PUBLISHED', '2026-06-01 00:00:00');

        $this->actingAs($this->createUser(['admin' => 1]))
            ->get('/admin/events')
            ->assertOk()
            ->assertSeeInOrder(['<td>Newest LAN</td>', '<td>Middle LAN</td>', '<td>Oldest LAN</td>'], false);
    }

    public function testDraftAndPrivateEventsAreStillListed()
    {
        $this->makeEvent('Draft LAN', '2027-03-01 16:00:00', 'DRAFT');
        $this->makeEvent('Private LAN', '2027-02-01 16:00:00', 'PRIVATE');
        $this->makeEvent('Public LAN', '2027-01-01 16:00:00');

        $this->actingAs($this->createUser(['admin' => 1]))
            ->get('/admin/events')
            ->assertOk()
            ->assertSeeInOrder(['<td>Draft LAN</td>', '<td>Private LAN</td>', '<td>Public LAN</td>'], false);
    }

    // The admin menu lists events too, so look at the table cells only
    public function testOlderEventsMoveToTheNextPage()
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->makeEvent(sprintf('Event Number %02d', $i), sprintf('2020-%02d-01 16:00:00', $i));
        }
        $admin = $this->createUser(['admin' => 1]);

        $this->actingAs($admin)->get('/admin/events')
            ->assertOk()
            ->assertSee('<td>Event Number 11</td>', false)
            ->assertSee('<td>Event Number 02</td>', false)
            ->assertDontSee('<td>Event Number 01</td>', false);

        $this->actingAs($admin)->get('/admin/events?page=2')
            ->assertOk()
            ->assertSee('<td>Event Number 01</td>', false)
            ->assertDontSee('<td>Event Number 02</td>', false);
    }
}
