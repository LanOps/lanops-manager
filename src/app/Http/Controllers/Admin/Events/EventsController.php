<?php

namespace App\Http\Controllers\Admin\Events;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

use App\Models\Event;
use App\Models\EventTimetable;
use App\Models\EventParticipant;
use App\Models\EventTimetableData;
use App\Models\EventTicket;
use App\Models\EventSeatingPlan;
use App\Models\EventInformation;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Http;

use Spatie\WebhookServer\WebhookCall;

class EventsController extends Controller
{
    /**
     * Show Events Page Index
     * @return View
     */
    public function index()
    {
        return view('admin.events.index')
            ->withUser(Auth::user())
            ->withEvents(Event::withoutGlobalScopes()->orderBy('created_at', 'DESC')->paginate(10));
    }

    /**
     * Show Events Page
     * @param  Event  $event
     * @return View
     */
    public function show(Event $event)
    {
        return view('admin.events.show')
            ->withUser(Auth::user())
            ->withEvent($event)
            ->withParticipants($event->eventParticipants()->paginate(10, ['*'], 'ep'));
    }

    /**
     * Add Event to Database
     * @param  Request $request
     * @return Redirect
     */
    public function store(Request $request)
    {
        $rules = [
            'event_name'    => 'required|unique:events,display_name',
            'desc_short'    => 'required',
            'desc_long'     => 'required',
            'end_date'      => 'required|date_format:Y-m-d',
            'end_time'      => 'required|date_format:H:i',
            'start_date'    => 'required|date_format:Y-m-d',
            'start_time'    => 'required|date_format:H:i',
            'capacity'      => 'required|integer',
            'venue'         => 'exists:event_venues,id',
        ];
        $messages = [
            'event_name.required'       => 'Event name is required',
            'start_date.required'       => 'Start date is required',
            'start_time.required'       => 'Start date is required',
            'end_date.required'         => 'End date is required',
            'end_time.required'         => 'End date is required',
            'end_date.date_format'      => 'End Date must be Y-m-d format',
            'end_time.date_format'      => 'End Time must be H:i format',
            'start_date.date_format'    => 'Start Date must be Y-m-d format',
            'start_time.date_format'    => 'Start Time must be H:i format',
            'desc_short.required'       => 'Short Description is required',
            'desc_long.required'        => 'Long Description is required',
            'capacity.required'         => 'Capacity is required',
            'capacity.integer'          => 'Capacity must be a integer',
            'venue.exists'              => 'A venue is required'

        ];
        $this->validate($request, $rules, $messages);

        $event                      = new Event();
        $event->display_name        = $request->event_name;
        $event->nice_name           = strtolower(str_replace(' ', '-', $request->event_name));
        $event->start               = date("Y-m-d H:i:s", strtotime($request->start_date . ' ' . $request->start_time));
        $event->end                 = date("Y-m-d H:i:s", strtotime($request->end_date . ' ' . $request->end_time));
        $event->desc_long           = $request->desc_long;
        $event->desc_short          = $request->desc_short;
        $event->event_venue_id      = @$request->venue;
        $event->capacity            = $request->capacity;

        if (!$event->save()) {
            Session::flash('alert-danger', 'Cannot Save Event!');
            return Redirect::to('admin/events/' . $event->slug);
        }
        Session::flash('alert-success', 'Successfully saved Event!');
        return Redirect::to('admin/events/' . $event->slug);
    }
    
    /**
     * Update Event
     * @param  Event   $event
     * @param  Request $request
     * @return Redirect
     */
    public function update(Event $event, Request $request)
    {
        $rules = [
            'event_name'        => 'filled',
            'end_date'          => 'filled|date_format:Y-m-d',
            'end_time'          => 'filled|date_format:H:i',
            'start_date'        => 'filled|date_format:Y-m-d',
            'start_time'        => 'filled|date_format:H:i',
            'status'            => 'in:draft,preview,published,private',
            'capacity'          => 'filled|integer',
            'venue'             => 'exists:event_venues,id',
            'type'              => 'in:LAN,TABLETOP',
        ];
        $messages = [
            'event_name.filled'         => 'Event Name cannot be empty',
            'end_date.filled'           => 'A End Date cannot be empty',
            'end_date.date_format'      => 'A End Date must be Y-m-d format',
            'end_time.filled'           => 'A End Time cannot be empty',
            'end_time.date_format'      => 'A End Time must be H:i formate',
            'start_date.filled'         => 'A Start Date cannut be empty',
            'start_date.date_format'    => 'A Start Date must be Y-m-d format',
            'start_time.filled'         => 'A Start Time cannot be empty',
            'start_time.date_format'    => 'A Start Time must be H:i format',
            'status.in'                 => 'Status must be draft, preview, published or private',
            'capacity.filled'           => 'Capacity is required',
            'capacity.integer'          => 'Capacity must be a integer',
            'venue.exists'              => 'A venue is required',
            'type.in'                   => 'Type must be LAN or TABLETOP',

        ];
        $this->validate($request, $rules, $messages);

        if (isset($request->event_name)) {
            $event->display_name    = $request->event_name;
            $event->nice_name       = strtolower(str_replace(' ', '-', $request->event_name));
        }

        if (isset($request->start_date)) {
            $start = $request->start_date . ' ' . date("H:i:s", strtotime($event->start));
            $event->start           = date("Y-m-d H:i:s", strtotime($start));
        }

        if (isset($request->start_time)) {
            $start = date("Y-m-d", strtotime($event->start)) . $request->start_time;
            $event->start           = date("Y-m-d H:i:s", strtotime($start));
        }

        if (isset($request->end_date)) {
            $end = $request->end_date . ' ' . date("H:i:s", strtotime($event->end));
            $event->end             = date("Y-m-d H:i:s", strtotime($end));
        }

        if (isset($request->end_time)) {
            $end = date("Y-m-d", strtotime($event->end)) . $request->end_time;
            $event->end             = date("Y-m-d H:i:s", strtotime($end));
        }

        if (isset($request->desc_long)) {
            $event->desc_long       = $request->desc_long;
        }

        if (isset($request->desc_short)) {
            $event->desc_short      = $request->desc_short;
        }

        if (isset($request->status)) {
            $event->status          = strtoupper($request->status);
        }

        if (isset($request->capacity)) {
            $event->capacity        = $request->capacity;
        }

        
        if (isset($request->type)) {
            $event->type            = $request->type;
        }

        if (!$event->save()) {
            Session::flash('alert-danger', 'Cannot update Event!');
            return Redirect::to('admin/events/' . $event->slug);
        }

        Session::flash('alert-success', 'Successfully updated Event!');
        return Redirect::to('admin/events/' . $event->slug);
    }

    /**
     * Make a new secret link for a Private event; the old link stops working
     * @param  Event  $event
     * @return Redirect
     */
    public function regeneratePrivateLink(Event $event)
    {
        if ($event->status != 'PRIVATE') {
            Session::flash('alert-danger', 'Only Private events have a private link.');
            return Redirect::to('admin/events/' . $event->slug);
        }
        $event->private_key = Event::generatePrivateKey();
        if (!$event->save()) {
            Session::flash('alert-danger', 'Cannot make a new private link!');
            return Redirect::to('admin/events/' . $event->slug);
        }
        Session::flash('alert-success', 'Made a new private link. The old link no longer works.');
        return Redirect::to('admin/events/' . $event->slug);
    }

    /**
     * Delete Event from Database
     * @param  Event  $event
     * @return Redirect
     */
    public function destroy(Event $event)
    {
        if (!$event->eventParticipants->isEmpty()) {
            Session::flash('alert-danger', 'Cannot delete event with participants!');
            return Redirect::back();
        }

        if (!$event->delete()) {
            Session::flash('alert-danger', 'Cannot delete Event!');
            return Redirect::back();
        }

        Session::flash('alert-success', 'Successfully deleted Event!');
        return Redirect::to('admin/events/');
    }

    /**
     * Add Gift Participant
     * @param  Request $request
     * @param  Event   $event
     * @return Redirect
     */
    public function freeGift(Request $request, Event $event)
    {
        $participant                            = new EventParticipant();
        $participant->user_id                   = $request->user_id;
        $participant->event_id                  = $event->id;
        $participant->free                      = 1;
        $participant->staff_free_assigned_by    = Auth::id();
        
        if (!$participant->save()) {
            Session::flash('alert-danger', 'Could not add Gift!');
            return Redirect::to('admin/events/' . $event->slug . '/tickets');
        }

        Session::flash('alert-success', 'Successfully added Gift!');
        return Redirect::to('admin/events/' . $event->slug . '/tickets');
    }

    /**
     * Add Admin Participant
     * @param  Request $request
     * @param  Event   $event
     * @return Redirect
     */
    public function freeStaff(Request $request, Event $event)
    {
        $participant = new EventParticipant();

        $participant->user_id                = $request->user_id;
        $participant->event_id               = $event->id;
        $participant->staff                  = 1;
        $participant->staff_free_assigned_by = Auth::id();

        if (!$participant->save()) {
            Session::flash('alert-danger', 'Could not add Admin!');
            return Redirect::to('admin/events/' . $event->slug . '/tickets');
        }

        Session::flash('alert-success', 'Successfully added Admin!');
        return Redirect::to('admin/events/' . $event->slug . '/tickets');
    }

    public function linkDiscord(Request $request, Event $event)
    {
        // Venue is optional on an event; skip blank lines so the address has no stray commas
        $address = array_filter([
            $event->venue?->address_1,
            $event->venue?->address_2,
            $event->venue?->address_street,
            $event->venue?->address_city,
            $event->venue?->address_postcode,
            $event->venue?->address_country,
        ]);
        $response = Http::withBasicAuth(
            config('app.discord_bot_user'),
            config('app.discord_bot_pass')
        )
        ->post(config('app.discord_bot_url') . '/events/create', [
            'name' => $event->display_name,
            'slug' => $event->slug,
            'url' => config('app.url') . '/events/' . $event->slug,
            'start' => $event->start,
            'end' => $event->end,
            'address' => implode(", ", $address)
        ]);

        if ($response->status() != 200) {
            Session::flash('alert-danger', 'Cannot Link Event! ' . $response);
            return Redirect::to('admin/events/' . $event->slug);
        }

        $event->discord_link_enabled = true;
        $event->discord_role_id = $response['role_id'];
        $event->discord_channel_id = $response['channel_id'];
        $event->discord_event_id = $response['event_id'];
        if (!$event->save()) {
            Session::flash('alert-danger', 'Cannot Link Event!');
            return Redirect::to('admin/events/' . $event->slug);
        }

        foreach ($event->eventParticipants as $participant) {
                WebhookCall::create()
                ->url(config('app.discord_bot_url') . '/participants/new')
                ->payload([
                    'username' => $participant->user->username,
                    'discord_id' => $participant->user->discord_id,
                    'channel_id' => $event->discord_channel_id,
                    'role_id' => $event->discord_role_id,
                    'no_message' => true
                ])
                ->useSecret(config('app.discord_bot_secret'))
                ->dispatch();
        }

        Session::flash('alert-success', 'Successfully Linked Event!');
        return Redirect::to('admin/events/' . $event->slug);
    }

    /**
     * Stop sending this event's attendees and updates to the Discord bot
     * @param  Event   $event
     * @return Redirect
     */
    public function unlinkDiscord(Event $event)
    {
        $event->discord_link_enabled = false;
        if (!$event->save()) {
            Session::flash('alert-danger', 'Cannot Unlink Event!');
            return Redirect::to('admin/events/' . $event->slug);
        }

        Session::flash('alert-success', 'Successfully Unlinked Event!');
        return Redirect::to('admin/events/' . $event->slug);
    }

    /**
     * Duplicate an Event as the next one: new name and start, with every date
     * (end, ticket sales, timetable slots) moved by the same amount
     * @param  Request $request
     * @param  Event   $event
     * @return Redirect
     */
    public function duplicate(Request $request, Event $event)
    {
        $this->validate($request, [
            'dup_name'      => 'required|string|max:255',
            'dup_start_date'      => 'required|date_format:Y-m-d',
            'dup_start_time'      => 'required|date_format:H:i',
            'copy_timetables' => 'nullable|boolean',
        ], [
            'dup_name.required'    => 'The new event needs a name',
            'dup_start_date.required'    => 'The new event needs a start date',
            'dup_start_date.date_format' => 'Start date must be Y-m-d format',
            'dup_start_time.required'    => 'The new event needs a start time',
            'dup_start_time.date_format' => 'Start time must be H:i format',
        ]);

        $niceName = strtolower(str_replace(' ', '-', $request->dup_name));
        if (Event::withoutGlobalScopes()->where('nice_name', $niceName)->exists()) {
            return Redirect::back()->withInput()->withErrors([
                'dup_name' => 'There is already an event called ' . $request->dup_name,
            ]);
        }

        // Dates are stored as local wall-clock times: do the sums in UTC so a
        // clock change between the two events doesn't move anything by an hour
        $oldStart = Carbon::parse($event->start, 'UTC');
        $newStart = Carbon::parse($request->dup_start_date . ' ' . $request->dup_start_time, 'UTC');
        $offset = (int) $oldStart->diffInSeconds($newStart, false);
        $shift = fn ($date) => $date === null
            ? null
            : Carbon::parse($date, 'UTC')->addSeconds($offset)->format('Y-m-d H:i:s');

        $mediaToCopy = [];
        $duplicatedEvent = DB::transaction(function () use ($request, $event, $niceName, $shift, &$mediaToCopy) {
            $duplicatedEvent                  = new Event();
            $duplicatedEvent->display_name    = $request->dup_name;
            $duplicatedEvent->nice_name       = $niceName;
            $duplicatedEvent->start           = $shift($event->start);
            $duplicatedEvent->end             = $shift($event->end);
            $duplicatedEvent->desc_long       = $event->desc_long;
            $duplicatedEvent->desc_short      = $event->desc_short;
            $duplicatedEvent->essential_info  = $event->essential_info;
            $duplicatedEvent->type            = $event->type;
            $duplicatedEvent->event_venue_id  = $event->event_venue_id;
            $duplicatedEvent->capacity        = $event->capacity;
            $duplicatedEvent->save();

            foreach ($event->tickets as $ticket) {
                $duplicatedTicket               = new EventTicket();
                $duplicatedTicket->event_id     = $duplicatedEvent->id;
                $duplicatedTicket->name         = $ticket->name;
                $duplicatedTicket->type         = $ticket->type;
                $duplicatedTicket->price        = $ticket->price;
                $duplicatedTicket->price_credit = $ticket->price_credit;
                $duplicatedTicket->seatable     = $ticket->seatable;
                $duplicatedTicket->sale_start   = $shift($ticket->sale_start);
                $duplicatedTicket->sale_end     = $shift($ticket->sale_end);
                $duplicatedTicket->quantity     = $ticket->quantity;
                $duplicatedTicket->save();
            }

            foreach (EventSeatingPlan::withoutGlobalScopes()->where('event_id', $event->id)->get() as $seatingPlan) {
                $duplicatedSeatingPlan              = new EventSeatingPlan();
                $duplicatedSeatingPlan->event_id    = $duplicatedEvent->id;
                $duplicatedSeatingPlan->name        = $seatingPlan->name;
                $duplicatedSeatingPlan->name_short  = $seatingPlan->name_short;
                $duplicatedSeatingPlan->columns     = $seatingPlan->columns;
                $duplicatedSeatingPlan->rows        = $seatingPlan->rows;
                $duplicatedSeatingPlan->status      = $seatingPlan->status;
                $duplicatedSeatingPlan->save();

                // Seats switched off in the old plan (pillars, the admin desk) stay off
                $disabledSeats = $seatingPlan->seats()->where('disabled', true)->pluck('seat');
                if ($disabledSeats->isNotEmpty()) {
                    $duplicatedSeatingPlan->seats()->whereIn('seat', $disabledSeats)->update(['disabled' => true]);
                }
                if ($seatingPlan->hasMedia()) {
                    $mediaToCopy[] = [$seatingPlan, $duplicatedSeatingPlan];
                }
            }

            foreach ($event->information as $information) {
                $duplicatedInformation               = new EventInformation();
                $duplicatedInformation->event_id     = $duplicatedEvent->id;
                $duplicatedInformation->title        = $information->title;
                $duplicatedInformation->text         = $information->text;
                $duplicatedInformation->order        = $information->order;
                $duplicatedInformation->save();
                if ($information->hasMedia()) {
                    $mediaToCopy[] = [$information, $duplicatedInformation];
                }
            }

            if ($request->boolean('copy_timetables')) {
                foreach (EventTimetable::withoutGlobalScopes()->with('data')->where('event_id', $event->id)->get() as $timetable) {
                    $duplicatedTimetable           = new EventTimetable();
                    $duplicatedTimetable->event_id = $duplicatedEvent->id;
                    $duplicatedTimetable->name     = $timetable->name;
                    $duplicatedTimetable->status   = $timetable->status;
                    $duplicatedTimetable->primary  = $timetable->primary;
                    $duplicatedTimetable->save();
                    foreach ($timetable->data as $slot) {
                        $duplicatedSlot                     = new EventTimetableData();
                        $duplicatedSlot->event_timetable_id = $duplicatedTimetable->id;
                        $duplicatedSlot->start_time         = $shift($slot->start_time);
                        $duplicatedSlot->name               = $slot->name;
                        $duplicatedSlot->desc               = $slot->desc;
                        $duplicatedSlot->save();
                    }
                }
            }

            return $duplicatedEvent;
        });

        // Images are files, so copy them once the database rows are safely in
        foreach ($mediaToCopy as [$from, $to]) {
            foreach ($from->getMedia() as $media) {
                $media->copy($to, $media->collection_name);
            }
        }

        Session::flash(
            'alert-success',
            'Created ' . $duplicatedEvent->display_name . ' as a draft, starting '
                . Carbon::parse($duplicatedEvent->start)->format('D j M Y H:i')
                . '. Ticket sale dates' . ($request->boolean('copy_timetables') ? ' and the timetable' : '')
                . ' moved to match.'
        );
        return Redirect::to('admin/events/' . $duplicatedEvent->slug);
    }
}
