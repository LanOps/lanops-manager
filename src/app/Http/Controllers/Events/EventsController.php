<?php

namespace App\Http\Controllers\Events;

use Auth;

use App\Models\Event;
use App\Models\EventParticipant;

use App\Http\Controllers\Controller;

class EventsController extends Controller
{
    /**
     * Show Events Index Page
     * @return View
     */
    public function index()
    {
        seo()
            ->title('Events')
            ->description('Browse all gaming events hosted by ' . config('app.name') . '. Book your ticket online.')
            ->url(url('/events'))
            ->image(config('app.url') . '/images/events-og-image.jpg');

        return view('events.index')->withEvents(
            Event::listed()->with(['seatingPlans.seats', 'eventParticipants', 'venue', 'tickets'])->orderByDesc('start')->get()
        );
    }
    
    /**
     * Open a Private event from its secret link, and remember the link for the
     * rest of the visit so buying tickets, checkout and seating work too
     * @param  string $key
     * @return Redirect
     */
    public function showPrivate($key)
    {
        if (!$event = Event::findByPrivateKey($key)) {
            abort(404);
        }
        $event->unlockForSession();
        return redirect('/events/' . $event->slug);
    }

    /**
     * Show Event Page
     * @param  Event $event
     * @return View
     */
    public function show(Event $event)
    {
        $eventDates = date('jS F Y', strtotime($event->start)) . ' - ' . date('jS F Y', strtotime($event->end));
        $seoDescription = $eventDates
            . ($event->venue ? ' at ' . $event->venue->display_name : '')
            . '. ' . strip_tags(substr($event->desc_long, 0, 120));

        seo()
            ->title($event->display_name)
            ->description($seoDescription)
            ->url(url('/events/' . $event->slug))
            ->image(config('app.url') . '/images/events-og-image.jpg');

        $user = Auth::user();
        if ($user) {
            $clauses = ['user_id' => $user->id, 'event_id' => $event->id];
            $user->eventParticipation = EventParticipant::where($clauses)
                ->with(['event', 'ticket', 'seat.seatingPlan'])
                ->get();
        }
        // Load everything the page shows up front, rather than one query per seat and attendee
        $event->load([
            'venue.media',
            'tickets',
            'seatingPlans.media',
            'seatingPlans.seats.eventParticipant.user',
            'eventParticipants.user',
            'eventParticipants.seat.seatingPlan',
            'information.media',
            'timetables.data',
            'polls.options.votes',
            'tournaments',
        ]);
        if (!$event->polls->isEmpty()) {
            foreach ($event->polls as $poll) {
                $poll->sortOptions();
            }
        }
        $response = response(view('events.show')->withEvent($event));
        if ($event->status == 'PRIVATE') {
            // Keep it out of search engines, even if the link is shared somewhere public
            $response->header('X-Robots-Tag', 'noindex, nofollow');
        }
        return $response;
    }
}
