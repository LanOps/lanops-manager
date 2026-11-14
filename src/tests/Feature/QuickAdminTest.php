<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventSeating;
use App\Models\EventSeatingPlan;
use App\Models\EventTicket;
use App\Models\EventTimetable;
use App\Models\EventTimetableData;
use App\Models\User;
use Tests\TestCase;

/**
 * Quicker admin jobs: duplicating an event as the next one, and signing
 * people in from the participants list.
 */
class QuickAdminTest extends TestCase
{
    private function participant(User $user, EventTicket $ticket, array $attributes = []): EventParticipant
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

    public function testDuplicatingMovesEveryDateAndCopiesTheTimetable()
    {
        $admin = $this->createUser(['admin' => 1]);
        $ticket = $this->createEventWithTicket(
            ['display_name' => 'LanOps 60', 'nice_name' => 'lanops-60', 'start' => '2026-05-15 16:00:00', 'end' => '2026-05-17 14:00:00', 'status' => 'PUBLISHED'],
            ['sale_start' => '2026-03-01 12:00:00', 'sale_end' => '2026-05-14 23:59:00']
        );
        $event = $ticket->event;
        $plan = new EventSeatingPlan();
        $plan->forceFill(['event_id' => $event->id, 'name' => 'Main Hall', 'name_short' => 'Main', 'columns' => 4, 'rows' => 2, 'status' => 'PUBLISHED'])->save();
        $plan->seats()->where('seat', 'A2')->update(['disabled' => true]);
        $timetable = new EventTimetable();
        $timetable->forceFill(['event_id' => $event->id, 'name' => 'Lan Start', 'status' => 'PUBLISHED', 'primary' => 1])->save();
        $slot = new EventTimetableData();
        $slot->forceFill(['event_timetable_id' => $timetable->id, 'start_time' => '2026-05-16 21:30:00', 'name' => 'Team Fortress 2', 'desc' => 'Gnome'])->save();

        // The event page suggests the next name
        $this->actingAs($admin)->get('/admin/events/' . $event->slug)
            ->assertOk()
            ->assertSee('value="LanOps 61"', false)
            ->assertSee('value="2026-05-15"', false);

        $this->actingAs($admin)->post('/admin/events/' . $event->slug . '/duplicate', [
            'dup_name'        => 'LanOps 61',
            'dup_start_date'  => '2026-11-13',
            'dup_start_time'  => '16:00',
            'copy_timetables' => '1',
        ])->assertRedirect('/admin/events/lanops-61')->assertSessionHas('alert-success');

        $copy = Event::withoutGlobalScopes()->where('nice_name', 'lanops-61')->firstOrFail();
        $this->assertSame('LanOps 61', $copy->display_name);
        $this->assertSame('DRAFT', $copy->status);
        $this->assertSame('2026-11-13 16:00:00', $copy->start);
        $this->assertSame('2026-11-15 14:00:00', $copy->end);

        // 182 days later, same wall-clock times
        $copyTicket = EventTicket::where('event_id', $copy->id)->firstOrFail();
        $this->assertSame('2026-08-30 12:00:00', $copyTicket->sale_start);
        $this->assertSame('2026-11-12 23:59:00', $copyTicket->sale_end);

        $copyPlan = EventSeatingPlan::withoutGlobalScopes()->where('event_id', $copy->id)->firstOrFail();
        $this->assertSame(8, $copyPlan->seats()->count());
        $this->assertSame(['A2'], $copyPlan->seats()->where('disabled', true)->pluck('seat')->all());

        $copyTimetable = EventTimetable::withoutGlobalScopes()->where('event_id', $copy->id)->firstOrFail();
        $this->assertSame('Lan Start', $copyTimetable->name);
        $copySlot = $copyTimetable->data()->firstOrFail();
        $this->assertSame('2026-11-14 21:30:00', $copySlot->start_time);
        $this->assertSame('Team Fortress 2', $copySlot->name);

        // The old event is untouched
        $this->assertSame('2026-05-16 21:30:00', $slot->fresh()->start_time);
        $this->assertSame('2026-03-01 12:00:00', $ticket->fresh()->sale_start);
    }

    public function testDuplicatingCanLeaveTheTimetablesBehind()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->createEventWithTicket()->event;
        $timetable = new EventTimetable();
        $timetable->forceFill(['event_id' => $event->id, 'name' => 'Main', 'status' => 'PUBLISHED'])->save();

        $this->actingAs($admin)->post('/admin/events/' . $event->slug . '/duplicate', [
            'dup_name'        => 'Next One',
            'dup_start_date'  => '2027-01-08',
            'dup_start_time'  => '18:00',
            'copy_timetables' => '0',
        ])->assertSessionHas('alert-success');

        $copy = Event::withoutGlobalScopes()->where('nice_name', 'next-one')->firstOrFail();
        $this->assertSame(0, EventTimetable::withoutGlobalScopes()->where('event_id', $copy->id)->count());
    }

    public function testDuplicatingNeedsAFreeName()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->createEventWithTicket(['display_name' => 'LanOps 60', 'nice_name' => 'lanops-60'])->event;
        $before = Event::withoutGlobalScopes()->count();

        $this->actingAs($admin)->from('/admin/events/' . $event->slug)->post('/admin/events/' . $event->slug . '/duplicate', [
            'dup_name' => 'LanOps 60', 'dup_start_date' => '2027-01-08', 'dup_start_time' => '18:00',
        ])->assertRedirect('/admin/events/' . $event->slug)->assertSessionHasErrors('dup_name');

        $this->actingAs($admin)->from('/admin/events/' . $event->slug)->post('/admin/events/' . $event->slug . '/duplicate', [])
            ->assertSessionHasErrors(['dup_name', 'dup_start_date', 'dup_start_time']);

        $this->assertSame($before, Event::withoutGlobalScopes()->count());
    }

    public function testParticipantsCanBeSearchedAndFilteredBySignIn()
    {
        $admin = $this->createUser(['admin' => 1]);
        $ticket = $this->createEventWithTicket();
        $event = $ticket->event;
        $alice = $this->participant($this->createUser(['username' => 'AliceZap', 'firstname' => 'Alice', 'surname' => 'Smith']), $ticket);
        $this->participant($this->createUser(['username' => 'BobBolt', 'firstname' => 'Bob', 'surname' => 'Jones']), $ticket, ['signed_in' => true]);
        $plan = new EventSeatingPlan();
        $plan->forceFill(['event_id' => $event->id, 'name' => 'Main', 'name_short' => 'Main', 'columns' => 4, 'rows' => 4])->save();
        $plan->seats()->where('seat', 'D4')->update(['event_participant_id' => $alice->id]);
        $list = '/admin/events/' . $event->slug . '/participants';

        $this->actingAs($admin)->get($list)
            ->assertOk()->assertSee('AliceZap')->assertSee('BobBolt')
            ->assertSee('Not signed in (1)')->assertSee('Signed in (1)');

        $this->actingAs($admin)->get($list . '?search=alice+smith')
            ->assertOk()->assertSee('AliceZap')->assertDontSee('BobBolt');

        $this->actingAs($admin)->get($list . '?search=D4')
            ->assertOk()->assertSee('AliceZap')->assertDontSee('BobBolt');

        $this->actingAs($admin)->get($list . '?status=out')
            ->assertOk()->assertSee('AliceZap')->assertDontSee('BobBolt')
            ->assertSee($list . '/' . $alice->id . '/signin', false);

        $this->actingAs($admin)->get($list . '?search=nobody')
            ->assertOk()->assertSee('No participants match.');
    }

    public function testSigningInFromTheListGoesBackToTheList()
    {
        $admin = $this->createUser(['admin' => 1]);
        $ticket = $this->createEventWithTicket();
        $event = $ticket->event;
        $alice = $this->participant($this->createUser(['username' => 'AliceZap', 'firstname' => 'Alice', 'surname' => 'Smith']), $ticket);
        $list = '/admin/events/' . $event->slug . '/participants?search=alice';

        $this->actingAs($admin)->from($list)->post('/admin/events/' . $event->slug . '/participants/' . $alice->id . '/signin')
            ->assertRedirect($list)
            ->assertSessionHas('alert-success', 'Signed in AliceZap (Alice Smith).');

        $this->assertTrue((bool) $alice->fresh()->signed_in);
    }

    public function testAParticipantCanOnlyBeSignedInThroughTheirOwnEvent()
    {
        $admin = $this->createUser(['admin' => 1]);
        $ticket = $this->createEventWithTicket();
        $other = $this->createEventWithTicket()->event;
        $alice = $this->participant($this->createUser(), $ticket);

        $this->actingAs($admin)->post('/admin/events/' . $other->slug . '/participants/' . $alice->id . '/signin')
            ->assertNotFound();

        $this->assertFalse((bool) $alice->fresh()->signed_in);
    }
}
