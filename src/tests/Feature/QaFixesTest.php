<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventSeating;
use App\Models\EventSeatingPlan;
use App\Models\EventTicket;
use App\Models\EventTimetable;
use App\Models\EventTimetableData;
use App\Models\GalleryAlbum;
use App\Models\Poll;
use App\Models\Tournament;
use App\Models\User;
use Tests\TestCase;

/**
 * Bugs found in the October 2026 QA pass.
 */
class QaFixesTest extends TestCase
{
    protected function createParticipant(User $user, EventTicket $ticket, array $attributes = []): EventParticipant
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

    protected function createSeatingPlan(EventTicket $ticket, array $attributes = []): EventSeatingPlan
    {
        $plan = factory(EventSeatingPlan::class)->make();
        $plan->forceFill(array_merge(['event_id' => $ticket->event_id, 'status' => 'PUBLISHED'], $attributes));
        $plan->save();

        return $plan->fresh();
    }

    protected function seat(EventParticipant $participant, EventSeatingPlan $plan, string $seat): void
    {
        EventSeating::where('event_seating_plan_id', $plan->id)->where('seat', $seat)
            ->update(['event_participant_id' => $participant->id]);
    }

    protected function removeSeat(User $user, EventSeatingPlan $plan, EventParticipant $participant)
    {
        // As the "Remove Seating" buttons on the event and account pages send it
        return $this->actingAs($user)
            ->from('/account')
            ->delete('/events/' . $participant->event->slug . '/seating/' . $plan->slug, [
                'user_id'        => $user->id,
                'participant_id' => $participant->id,
                'seat_number'    => 'A1',
            ]);
    }

    public function testScanningATicketTakesAnAdminToTheParticipantPage()
    {
        $admin = $this->createUser(['admin' => 1]);
        $ticket = $this->createEventWithTicket();
        $participant = $this->createParticipant($this->createUser(), $ticket, ['qr_token' => 'scan-me']);
        $page = '/admin/events/' . $ticket->event->slug . '/participants/' . $participant->id;

        $this->actingAs($admin)->get('/tickets/retrieve/scan-me')->assertRedirect($page);
        $this->actingAs($admin)->get($page)->assertOk();
    }

    public function testScanningATicketTakesAnAttendeeToTheEventPage()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket(['status' => 'PUBLISHED']);
        $this->createParticipant($user, $ticket, ['qr_token' => 'scan-me']);

        $this->actingAs($user)->get('/tickets/retrieve/scan-me')
            ->assertRedirect('/events/' . $ticket->event->slug);
        $this->actingAs($user)->get('/events/' . $ticket->event->slug)->assertOk();
    }

    public function testYouCanRemoveYourOwnSeat()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();
        $plan = $this->createSeatingPlan($ticket);
        $participant = $this->createParticipant($user, $ticket);
        $this->seat($participant, $plan, 'A1');

        $this->removeSeat($user, $plan, $participant)
            ->assertRedirect('/account')
            ->assertSessionHas('alert-success');

        $this->assertNull($participant->fresh()->seat);
    }

    public function testYouCannotRemoveSomeoneElsesSeat()
    {
        $owner = $this->createUser();
        $ticket = $this->createEventWithTicket();
        $plan = $this->createSeatingPlan($ticket);
        $participant = $this->createParticipant($owner, $ticket);
        $this->seat($participant, $plan, 'A1');

        $this->removeSeat($this->createUser(), $plan, $participant)
            ->assertSessionHas('alert-danger', 'Event Participant not found');

        $this->assertSame('A1', $participant->fresh()->seat->seat);
    }

    public function testYouCannotRemoveYourSeatOnceSeatingIsLocked()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket();
        $plan = $this->createSeatingPlan($ticket, ['locked' => true]);
        $participant = $this->createParticipant($user, $ticket);
        $this->seat($participant, $plan, 'A1');

        $this->removeSeat($user, $plan, $participant)
            ->assertSessionHas('alert-danger', 'Seating is locked for this event');

        $this->assertSame('A1', $participant->fresh()->seat->seat);
    }

    protected function pastEvent(): Event
    {
        return $this->createEventWithTicket([
            'start' => now()->subMonth()->format('Y-m-d H:i:s'),
            'end'   => now()->subMonth()->addDays(2)->format('Y-m-d H:i:s'),
        ])->event;
    }

    public function testEditingATournamentKeepsItsPastEvent()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->pastEvent();
        $tournament = new Tournament();
        $tournament->forceFill(['name' => 'Rocket League', 'team_size' => 0, 'event_id' => $event->id])->save();
        // New tournaments always start as drafts
        $tournament->forceFill(['status' => 'LIVE'])->save();

        // The form offers the linked event, selected, so saving it as it is keeps the link
        $this->actingAs($admin)->get('/admin/tournaments/' . $tournament->slug)
            ->assertOk()
            ->assertSee('<option value="' . $event->id . '" selected="selected">', false);

        $this->actingAs($admin)->post('/admin/tournaments/' . $tournament->slug, [
            'name'      => 'Rocket League',
            'status'    => 'COMPLETE',
            'team_size' => 0,
            'event_id'  => $event->id,
        ])->assertSessionHas('alert-success');

        $this->assertSame($event->id, $tournament->fresh()->event_id);
    }

    public function testEditingAPollKeepsItsPastEvent()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->pastEvent();
        $poll = new Poll();
        $poll->forceFill(['name' => 'Next game', 'user_id' => $admin->id, 'status' => 'PUBLISHED', 'event_id' => $event->id])->save();

        $this->actingAs($admin)->get('/admin/polls/' . $poll->fresh()->slug)
            ->assertOk()
            ->assertSee('<option value="' . $event->id . '" selected="selected">', false)
            ->assertSee(url('/polls/' . $poll->fresh()->slug));
    }

    public function testTimetableSlotEditButtonCopesWithQuotes()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->createEventWithTicket()->event;
        $timetable = new EventTimetable();
        $timetable->forceFill(['event_id' => $event->id, 'name' => 'Main', 'status' => 'PUBLISHED'])->save();
        $slot = new EventTimetableData();
        $slot->forceFill([
            'event_timetable_id' => $timetable->id,
            'start_time'         => $event->start,
            'name'               => "Players' choice",
            'desc'               => "Line one\nLine \"two\"",
        ])->save();

        $html = $this->actingAs($admin)
            ->get('/admin/events/' . $event->slug . '/timetables/' . $timetable->fresh()->slug)
            ->assertOk()
            ->getContent();

        // The name and description go into the onclick handler as JavaScript strings, escaped
        $this->assertStringContainsString("Players\\u0027 choice", $html);
        $this->assertStringNotContainsString("'Players&#039; choice'", $html);
    }

    public function testABlankSeatingPlanShortNameFallsBackToTheName()
    {
        $admin = $this->createUser(['admin' => 1]);
        $ticket = $this->createEventWithTicket();
        $plan = $this->createSeatingPlan($ticket, ['name' => 'Main Hall', 'columns' => 4, 'rows' => 3]);

        $this->actingAs($admin)->post('/admin/events/' . $ticket->event->slug . '/seating/' . $plan->slug, [
            'name'       => 'Main Hall',
            'name_short' => '',
            'columns'    => 4,
            'rows'       => 3,
            'status'     => 'published',
        ])->assertSessionHas('alert-success');

        $this->assertNull($plan->fresh()->name_short);
        $this->assertSame('Main Hall', $plan->fresh()->getShortName());

        // Plans saved blank before this fix
        $plan->forceFill(['name_short' => ''])->saveQuietly();
        $this->assertSame('Main Hall', $plan->fresh()->getShortName());
    }

    public function testASeatingPlanCanBeWiderThan26Columns()
    {
        $admin = $this->createUser(['admin' => 1]);
        $ticket = $this->createEventWithTicket();
        $plan = $this->createSeatingPlan($ticket, ['columns' => 4, 'rows' => 2]);

        $this->actingAs($admin)->post('/admin/events/' . $ticket->event->slug . '/seating/' . $plan->slug, [
            'name'    => $plan->name,
            'columns' => 30,
            'rows'    => 2,
        ])->assertSessionHas('alert-success');

        $this->assertSame(60, $plan->fresh()->seats()->count());
        $this->assertSame(['A', 'B'], $plan->fresh()->headers);
    }

    public function testAnEmptyGalleryAlbumPageLoads()
    {
        $album = new GalleryAlbum();
        $album->forceFill(['name' => 'Coming soon', 'description' => 'Photos to follow', 'status' => 'PUBLISHED'])->save();

        $this->get('/gallery/' . $album->fresh()->slug)->assertOk()->assertSee('Coming soon');
    }
}
