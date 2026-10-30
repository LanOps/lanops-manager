<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventTimetable;
use App\Models\EventTimetableData;
use App\Models\Purchase;
use Tests\TestCase;

/**
 * Low priority bugs from the October 2026 QA pass.
 */
class QaLowFixesTest extends TestCase
{
    public function testRenamingAnEventKeepsItsAddress()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->createEventWithTicket()->event;
        $slug = $event->slug;

        $this->actingAs($admin)->post('/admin/events/' . $slug, ['event_name' => 'LanOps 99 Renamed'])
            ->assertRedirect('/admin/events/' . $slug)
            ->assertSessionHas('alert-success');

        $event = Event::withoutGlobalScopes()->find($event->id);
        $this->assertSame('LanOps 99 Renamed', $event->display_name);
        $this->assertSame($slug, $event->slug);
        $this->actingAs($admin)->get('/admin/events/' . $slug)->assertOk();
    }

    public function testUnlinkingDiscordStopsSendingUpdates()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->createEventWithTicket()->event;
        $event->forceFill(['discord_link_enabled' => true, 'discord_channel_id' => '123'])->save();

        $this->actingAs($admin)->post('/admin/events/' . $event->slug . '/discord/unlink')
            ->assertRedirect('/admin/events/' . $event->slug)
            ->assertSessionHas('alert-success');

        $this->assertFalse((bool) Event::withoutGlobalScopes()->find($event->id)->discord_link_enabled);
    }

    public function testAdminsCanDeleteATimetableSlot()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->createEventWithTicket()->event;
        $timetable = new EventTimetable();
        $timetable->forceFill(['event_id' => $event->id, 'name' => 'Main', 'status' => 'PUBLISHED'])->save();
        $slot = new EventTimetableData();
        $slot->forceFill(['event_timetable_id' => $timetable->id, 'start_time' => $event->start, 'name' => 'Quiz'])->save();
        $page = '/admin/events/' . $event->slug . '/timetables/' . $timetable->fresh()->slug;

        $this->actingAs($admin)->get($page)->assertOk()->assertSee($page . '/data/' . $slot->id, false);

        $this->actingAs($admin)->from($page)->delete($page . '/data/' . $slot->id)
            ->assertRedirect($page)
            ->assertSessionHas('alert-success');

        $this->assertNull(EventTimetableData::find($slot->id));
    }

    public function testATimetableSlotCanOnlyBeDeletedThroughItsOwnTimetable()
    {
        $admin = $this->createUser(['admin' => 1]);
        $event = $this->createEventWithTicket()->event;
        $timetables = [];
        foreach (['Main', 'Side'] as $name) {
            $timetable = new EventTimetable();
            $timetable->forceFill(['event_id' => $event->id, 'name' => $name, 'status' => 'PUBLISHED'])->save();
            $timetables[] = $timetable->fresh();
        }
        $slot = new EventTimetableData();
        $slot->forceFill(['event_timetable_id' => $timetables[0]->id, 'start_time' => $event->start, 'name' => 'Quiz'])->save();

        $this->actingAs($admin)
            ->delete('/admin/events/' . $event->slug . '/timetables/' . $timetables[1]->slug . '/data/' . $slot->id)
            ->assertNotFound();

        $this->assertNotNull(EventTimetableData::find($slot->id));
    }

    public function testTheAdminUsernameBoxShowsTheCurrentUsername()
    {
        $admin = $this->createUser(['admin' => 1]);
        $user = $this->createUser(['username' => 'FragMaster']);

        $this->actingAs($admin)->get('/admin/users/' . $user->id)
            ->assertOk()
            ->assertSee('name="username" id="username" value="FragMaster"', false);
    }

    public function testTheAdminParticipantDebugRouteIsGone()
    {
        $admin = $this->createUser(['admin' => 1]);
        $ticket = $this->createEventWithTicket();
        $participant = EventParticipant::withoutEvents(fn () => EventParticipant::create([
            'user_id'   => $this->createUser()->id,
            'event_id'  => $ticket->event_id,
            'ticket_id' => $ticket->id,
        ]));

        $this->actingAs($admin)
            ->post('/admin/events/' . $ticket->event->slug . '/participants/' . $participant->id)
            ->assertStatus(405);
    }

    public function testTicketSalesTakeAReferralDiscountOffOncePerPurchase()
    {
        $user = $this->createUser();
        $ticket = $this->createEventWithTicket([], ['price' => 30]);
        $purchase = Purchase::withoutEvents(function () use ($user) {
            $purchase = new Purchase();
            $purchase->forceFill([
                'user_id'                 => $user->id,
                'type'                    => 'Stripe',
                'transaction_id'          => 'tx',
                'token'                   => 'pi_1',
                'status'                  => 'Success',
                'basket'                  => [],
                'total'                   => 55,
                'total_before_discount'   => 60,
                'referral_discount_total' => 5,
            ])->save();

            return $purchase;
        });
        EventParticipant::withoutEvents(function () use ($user, $ticket, $purchase) {
            foreach ([1, 2] as $i) {
                EventParticipant::create([
                    'user_id'     => $user->id,
                    'event_id'    => $ticket->event_id,
                    'ticket_id'   => $ticket->id,
                    'purchase_id' => $purchase->id,
                ]);
            }
        });

        // Two £30 tickets in one purchase with £5 off is £55, not £50
        $this->assertEquals(55, Event::withoutGlobalScopes()->find($ticket->event_id)->getTicketSalesCount());
    }
}
