<?php

namespace App\Http\Controllers\Admin\Events;

use Illuminate\Support\Facades\Session;

use App\Models\Event;
use App\Models\EventParticipant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ParticipantsController extends Controller
{
    /**
     * Show Participants Index Page, optionally filtered by ?search= and
     * ?status=in (signed in) / out (not signed in yet)
     * @param  Event   $event
     * @param  Request $request
     * @return View
     */
    public function index(Event $event, Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = in_array($request->query('status'), ['in', 'out'], true) ? $request->query('status') : '';

        $participants = $event->eventParticipants()->with('user', 'seat', 'ticket', 'purchase');
        if ($search !== '') {
            // Escape LIKE wildcards so "%" and "_" match literally
            $like = '%' . addcslashes($search, '\\%_') . '%';
            $participants->where(function ($query) use ($like, $search) {
                $query->whereHas('user', function ($user) use ($like) {
                    $user->where('username', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('firstname', 'like', $like)
                        ->orWhere('surname', 'like', $like)
                        ->orWhereRaw("CONCAT(COALESCE(firstname, ''), ' ', COALESCE(surname, '')) LIKE ?", [$like])
                        ->orWhere('discord_username', 'like', $like);
                })->orWhereHas('seat', function ($seat) use ($search) {
                    $seat->where('seat', $search);
                });
            });
        }
        if ($status !== '') {
            $participants->where('signed_in', $status === 'in');
        }

        $total = $event->eventParticipants()->count();
        $signedIn = $event->eventParticipants()->where('signed_in', true)->count();

        return view('admin.events.participants.index')
            ->withEvent($event)
            ->withSearch($search)
            ->withStatus($status)
            ->withTotal($total)
            ->withSignedIn($signedIn)
            ->withParticipants($participants->orderBy('id')->paginate(25)->withQueryString());
    }

    /**
     * Show Participants Page
     * @param  Event            $event
     * @param  EventParticipant $participant
     * @return View
     */
    public function show(Event $event, EventParticipant $participant)
    {
        return view('admin.events.participants.show')
            ->withEvent($event)
            ->withParticipant($participant);
    }

    /**
     * Sign in to Event
     * @param  Event            $event
     * @param  EventParticipant $participant
     * @return Redirect
     */
    public function signIn(Event $event, EventParticipant $participant)
    {
        if ($participant->event_id != $event->id) {
            abort(404);
        }
        $fallback = 'admin/events/' . $event->slug . '/participants/' . $participant->id;
        if (!$participant->setSignIn()) {
            Session::flash('alert-danger', 'Cannot sign in Participant!');
            return Redirect::back(302, [], $fallback);
        }
        $name = $participant->user ? $participant->user->username : 'Participant';
        if ($participant->user && trim($participant->user->firstname . ' ' . $participant->user->surname) !== '') {
            $name .= ' (' . trim($participant->user->firstname . ' ' . $participant->user->surname) . ')';
        }
        if ($participant->seat) {
            $name .= ', seat ' . $participant->seat->seat;
        }
        Session::flash('alert-success', 'Signed in ' . $name . '.');
        // Back to wherever the button was: the participant page or the list (keeping its search)
        return Redirect::back(302, [], $fallback);
    }

    public function transfer(Event $event, EventParticipant $participant, Request $request)
    {
        $rules = [
            'event_id'  => 'required',
            'event_id'  => 'exists:events,id',
        ];
        $messages = [
            'event_id|required' => 'A Event ID is required.',
            'event_id|exists'   => 'A Event ID must exist.',
        ];
        $this->validate($request, $rules, $messages);
        if ($participant->signed_in) {
            Session::flash('alert-warning', 'Cannot tranfer Participant already signed in!');
            return Redirect::to('admin/events/' . $event->slug . '/participants/' . $participant->id);
        }
        if (!$participant->transfer($request->event_id)) {
            Session::flash('alert-danger', 'Cannot tranfer Participant!');
            return Redirect::to('admin/events/' . $event->slug . '/participants/' . $participant->id);
        }
        Session::flash('alert-success', 'Participant Transferred!');
        return Redirect::to('admin/events/' . $event->slug . '/participants/');
    }
}
