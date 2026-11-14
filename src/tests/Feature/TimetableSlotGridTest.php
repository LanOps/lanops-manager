<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventTimetable;
use App\Models\EventTimetableData;
use Tests\TestCase;

/**
 * The admin timetable page saves every slot in one go.
 */
class TimetableSlotGridTest extends TestCase
{
    private function makeTimetable(Event $event, string $name = 'Main'): EventTimetable
    {
        $timetable = new EventTimetable();
        $timetable->forceFill(['event_id' => $event->id, 'name' => $name, 'status' => 'PUBLISHED'])->save();
        return $timetable->fresh();
    }

    private function makeSlot(EventTimetable $timetable, string $start, string $name, ?string $desc = null): EventTimetableData
    {
        $slot = new EventTimetableData();
        $slot->forceFill([
            'event_timetable_id' => $timetable->id,
            'start_time'         => $start,
            'name'               => $name,
            'desc'               => $desc,
        ])->save();
        return $slot;
    }

    private function page(Event $event, EventTimetable $timetable): string
    {
        return '/admin/events/' . $event->slug . '/timetables/' . $timetable->slug;
    }

    public function testTheGridAddsUpdatesAndRemovesSlotsInOneSave()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->createEventWithTicket()->event;
        $day = substr($event->start, 0, 10);
        $timetable = $this->makeTimetable($event);
        $keep = $this->makeSlot($timetable, $day . ' 16:00:00', 'Lan Go!');
        $gone = $this->makeSlot($timetable, $day . ' 20:00:00', 'Opening');
        $page = $this->page($event, $timetable);

        $this->actingAs($admin)->from($page)->post($page . '/slots', [
            'slots' => [
                ['id' => $keep->id, 'day' => $day, 'time' => '16:30', 'name' => 'LAN Start', 'desc' => 'Network up', 'remove' => 0],
                ['id' => $gone->id, 'day' => $day, 'time' => '20:00', 'name' => 'Opening', 'desc' => '', 'remove' => 1],
                ['day' => $day, 'time' => '20:30', 'name' => 'Friday Fight Night', 'desc' => 'Luke', 'remove' => 0],
                ['day' => $day, 'time' => '23:00', 'name' => 'BAR', 'desc' => 'Paul', 'remove' => 0],
                // A blank new row is skipped
                ['day' => $day, 'time' => '', 'name' => '', 'desc' => '', 'remove' => 0],
            ],
        ])->assertRedirect($page)->assertSessionHas('alert-success', 'Saved timetable: 2 added, 1 updated, 1 removed.');

        $this->assertNull(EventTimetableData::find($gone->id));
        $keep->refresh();
        $this->assertSame($day . ' 16:30:00', $keep->start_time);
        $this->assertSame('LAN Start', $keep->name);
        $this->assertSame('Network up', $keep->desc);
        $this->assertSame(
            ['LAN Start', 'Friday Fight Night', 'BAR'],
            $timetable->fresh()->data->pluck('name')->all()
        );
    }

    public function testAMissingNameKeepsEveryTypedRow()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->createEventWithTicket()->event;
        $day = substr($event->start, 0, 10);
        $timetable = $this->makeTimetable($event);
        $page = $this->page($event, $timetable);

        $this->actingAs($admin)->from($page)->post($page . '/slots', [
            'slots' => [
                ['day' => $day, 'time' => '16:00', 'name' => 'LAN Start', 'desc' => ''],
                ['day' => $day, 'time' => '20:00', 'name' => '', 'desc' => 'Matt'],
                ['day' => $day, 'time' => 'soon', 'name' => 'Quiz', 'desc' => ''],
            ],
        ])->assertRedirect($page)->assertSessionHasErrors(['slots.1.name', 'slots.2.time']);

        $this->assertSame(0, EventTimetableData::count());

        // The page shows the typed rows again, with the row numbers in the errors
        $this->followingRedirects()->actingAs($admin)->from($page)->post($page . '/slots', [
            'slots' => [
                ['day' => $day, 'time' => '16:00', 'name' => 'LAN Start', 'desc' => ''],
                ['day' => $day, 'time' => '20:00', 'name' => '', 'desc' => 'Matt'],
            ],
        ])->assertOk()->assertSee('Row 2 needs a name')->assertSee('value="LAN Start"', false)->assertSee('value="Matt"', false);
    }

    public function testSlotsFromAnotherTimetableCannotBeChanged()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->createEventWithTicket()->event;
        $day = substr($event->start, 0, 10);
        $main = $this->makeTimetable($event, 'Main');
        $side = $this->makeTimetable($event, 'Side');
        $other = $this->makeSlot($side, $day . ' 18:00:00', 'Side quest');
        $page = $this->page($event, $main);

        $this->actingAs($admin)->from($page)->post($page . '/slots', [
            'slots' => [['id' => $other->id, 'day' => $day, 'time' => '19:00', 'name' => 'Hijacked', 'remove' => 0]],
        ])->assertRedirect($page)->assertSessionHasErrors('slots');

        $this->actingAs($admin)->from($page)->post($page . '/data/' . $other->id, [
            'name' => 'Hijacked', 'start_time' => $day . ' 19:00:00',
        ])->assertNotFound();

        $this->assertSame('Side quest', $other->fresh()->name);
    }

    public function testATimetableFromAnotherEventIsRejected()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->createEventWithTicket()->event;
        $otherEvent = $this->createEventWithTicket()->event;
        $timetable = $this->makeTimetable($otherEvent);

        $this->actingAs($admin)->post($this->page($event, $timetable) . '/slots', [
            'slots' => [['day' => substr($event->start, 0, 10), 'time' => '12:00', 'name' => 'X']],
        ])->assertNotFound();
    }

    public function testNonAdminsCannotSaveSlots()
    {
        $user = $this->createUser();
        $event = $this->createEventWithTicket()->event;
        $timetable = $this->makeTimetable($event);

        $this->actingAs($user)->post($this->page($event, $timetable) . '/slots', [
            'slots' => [['day' => substr($event->start, 0, 10), 'time' => '12:00', 'name' => 'X']],
        ]);

        $this->assertSame(0, EventTimetableData::count());
    }

    public function testThePageOffersAnotherEventsTimetableMovedOntoThisEventsDays()
    {
        $admin = $this->createUser(['admin' => 1]);
        $last = $this->createEventWithTicket(['start' => '2026-05-15 16:00:00', 'end' => '2026-05-17 18:00:00'])->event;
        $next = $this->createEventWithTicket(['start' => '2026-11-13 16:00:00', 'end' => '2026-11-15 18:00:00'])->event;
        $old = $this->makeTimetable($last, 'Lan Start');
        $this->makeSlot($old, '2026-05-16 21:30:00', 'Team Fortress 2', 'Gnome');
        $timetable = $this->makeTimetable($next);

        $this->actingAs($admin)->get($this->page($next, $timetable))
            ->assertOk()
            ->assertSee('Copy from another event')
            ->assertSee($last->display_name . ' - Lan Start (1 slots)')
            // Saturday 16 May moves to Saturday 14 Nov, same time
            ->assertSee('"day":"2026-11-14","time":"21:30","name":"Team Fortress 2","desc":"Gnome"', false)
            // The grid offers each day of the event
            ->assertSee('<option value="2026-11-13"', false)
            ->assertSee('<option value="2026-11-15"', false);
    }
}
