<?php

namespace Tests\Feature;

use App\Models\EventParticipant;
use App\Models\EventSeatingPlan;
use App\Models\EventTicket;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollOptionVote;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pages that list attendees, seats or votes should run the same number of
 * queries however many there are, instead of one or more per row.
 */
class PerformanceTest extends TestCase
{
    protected function seatAttendees(EventTicket $ticket, EventSeatingPlan $plan, array $seats): void
    {
        foreach ($seats as $seat) {
            $participant = EventParticipant::withoutEvents(fn () => EventParticipant::create([
                'user_id'   => $this->createUser()->id,
                'event_id'  => $ticket->event_id,
                'ticket_id' => $ticket->id,
            ]));
            $plan->seats()->where('seat', $seat)->update(['event_participant_id' => $participant->id]);
        }
    }

    protected function countQueries(string $url): int
    {
        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });
        $this->get($url)->assertOk();

        return $count;
    }

    protected function createPlan(EventTicket $ticket): EventSeatingPlan
    {
        $plan = factory(EventSeatingPlan::class)->make();
        $plan->forceFill(['event_id' => $ticket->event_id, 'status' => 'PUBLISHED'])->save();

        return $plan->fresh();
    }

    public function testEventPageQueriesDoNotGrowWithAttendees()
    {
        $small = $this->createEventWithTicket();
        $this->seatAttendees($small, $this->createPlan($small), ['A1', 'B2']);

        $large = $this->createEventWithTicket();
        $this->seatAttendees($large, $this->createPlan($large), ['A1', 'B2', 'C3', 'D4', 'E5', 'F6', 'A8', 'B8']);

        $smallCount = $this->countQueries('/events/' . $small->event->slug);
        $largeCount = $this->countQueries('/events/' . $large->event->slug);

        $this->assertSame($smallCount, $largeCount);
    }

    public function testEventPageStillShowsWhoIsInEachSeat()
    {
        $ticket = $this->createEventWithTicket();
        $plan = $this->createPlan($ticket);
        $plan->seats()->where('seat', 'C3')->update(['disabled' => true]);
        $this->seatAttendees($ticket, $plan, ['B2']);
        $username = EventParticipant::first()->user->username;

        $response = $this->get('/events/' . $ticket->event->slug)->assertOk();

        // 6 x 8 seats, one disabled and one taken
        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/B2 -\s+' . $username . '\s/', $html);
        $this->assertMatchesRegularExpression('/C3 -\s+Disabled\s/', $html);
        $this->assertMatchesRegularExpression('/A1 -\s+Empty\s/', $html);
        $this->assertMatchesRegularExpression('/F8 -\s+Empty\s/', $html);
        $response->assertSee('46 / 47 Seats Remaining');
    }

    public function testEventsListQueriesDoNotGrowWithEvents()
    {
        $ticket = $this->createEventWithTicket();
        $this->seatAttendees($ticket, $this->createPlan($ticket), ['A1']);
        $oneEvent = $this->countQueries('/events');

        foreach (range(1, 4) as $i) {
            $ticket = $this->createEventWithTicket();
            $this->seatAttendees($ticket, $this->createPlan($ticket), ['A1']);
        }
        $fiveEvents = $this->countQueries('/events');

        $this->assertSame($oneEvent, $fiveEvents);
    }

    protected function addOption(Poll $poll, int $votes): void
    {
        $option = new PollOption();
        $option->forceFill(['name' => 'Option ' . uniqid(), 'poll_id' => $poll->id, 'user_id' => $poll->user_id])->save();
        foreach (range(1, $votes) as $i) {
            $vote = new PollOptionVote();
            $vote->forceFill(['poll_option_id' => $option->id, 'user_id' => $this->createUser()->id])->save();
        }
    }

    public function testPollsPagesQueriesDoNotGrowWithOptions()
    {
        $poll = new Poll();
        $poll->forceFill(['name' => 'Best game', 'status' => 'PUBLISHED', 'user_id' => $this->createUser()->id])->save();
        $this->addOption($poll, 1);
        $this->addOption($poll, 2);
        $index = $this->countQueries('/polls');
        $show = $this->countQueries('/polls/' . $poll->slug);

        foreach (range(1, 4) as $i) {
            $this->addOption($poll, 2);
        }

        $this->assertSame($index, $this->countQueries('/polls'));
        $this->assertSame($show, $this->countQueries('/polls/' . $poll->slug));
        $this->get('/polls')->assertSee('Votes: 11');
    }

    public function testAdminDashboardChartsOnlyTheLastTwelveMonths()
    {
        $ticket = $this->createEventWithTicket();
        foreach (['-2 years', '-2 days'] as $when) {
            $participant = EventParticipant::withoutEvents(fn () => EventParticipant::create([
                'user_id'   => $this->createUser()->id,
                'event_id'  => $ticket->event_id,
                'ticket_id' => $ticket->id,
            ]));
            $participant->forceFill(['created_at' => now()->modify($when)])->saveQuietly();
        }

        $this->actingAs($this->createUser(['admin' => 1]))->get('/admin')
            ->assertOk()
            ->assertSee('data-chart-labels="[&quot;' . now()->subDays(2)->format('M Y') . '&quot;]"', false)
            ->assertSee('data-chart-values="[1]"', false)
            ->assertDontSee(now()->subYears(2)->format('M Y'));
    }
}
