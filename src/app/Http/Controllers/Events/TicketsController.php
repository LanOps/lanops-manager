<?php

namespace App\Http\Controllers\Events;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

use App\Models\EventParticipant;
use App\Models\EventTicket;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

class TicketsController extends Controller
{
    /**
     * Purchase Ticket
     * @param  Request     $request
     * @param  EventTicket $ticket
     * @return Redirect
     */
    public function purchase(Request $request, EventTicket $ticket)
    {
        $user = Auth::user();

        // Hidden events (drafts, previews, Private events without the secret link) can't be bought from
        if (!$ticket->event) {
            abort(404);
        }
        if ($user == null) {
            Session::flash('alert-danger', 'User not found.');
            return Redirect::to('/events/' . $ticket->event->slug);
        }
        if ($ticket->event->status != 'PUBLISHED' && $ticket->event->status != 'PRIVATE') {
            Session::flash(
                'alert-danger',
                'Event is currently in ' . strtolower($ticket->event->status) . '. You cannot buy tickets yet.'
            );
            return Redirect::to('/events/' . $ticket->event->slug);
        }

        if (date('Y-m-d H:i:s') >= $ticket->event->end) {
            Session::flash('alert-danger', 'You cannot buy tickets for previous events.');
            return Redirect::to('/events/' . $ticket->event->slug);
        }

        if ($ticket->sale_start != null && date('Y-m-d H:i:s') <= $ticket->sale_start) {
            Session::flash('alert-danger', 'You cannot buy this ticket yet');
            return Redirect::to('/events/' . $ticket->event->slug);
        }

        if ($ticket->sale_end != null && date('Y-m-d H:i:s') >= $ticket->sale_end) {
            Session::flash('alert-danger', 'You cannot buy this ticket anymore');
            return Redirect::to('/events/' . $ticket->event->slug);
        }

        $this->validate($request, [
            'quantity' => 'required|integer|min:1|max:' . EventTicket::MAX_PER_ORDER,
        ], [
            'quantity.*' => 'You can buy between 1 and ' . EventTicket::MAX_PER_ORDER . ' tickets at a time.',
        ]);

        if ($soldOut = $ticket->soldOutMessage((int) $request->quantity)) {
            Session::flash('alert-danger', $soldOut);
            return Redirect::to('/events/' . $ticket->event->slug);
        }

        $params = [
            'tickets' => [
                $ticket->id => (int) $request->quantity,
            ],
            'referral_discount' => false
        ];
        if ($user->getAvailableReferralPurchase()) {
            $params['referral_discount'] = true;
        }
        Session::put(config('app.basket_name'), $params);
        Session::save();
        return Redirect::to('/payment/checkout');
    }

    /**
     * The page a ticket's QR code opens. The code holds a random token, so
     * only someone holding the ticket (or a photo of it) can reach this page.
     * - Admins get a door page with the holder, ticket, seat and a Sign in button.
     * - The ticket's owner gets their full ticket details.
     * - Anyone else, logged in or not, only sees the event, ticket type and
     *   whether the ticket is valid: no name, seat or purchase details.
     * Cancelled (refunded) tickets still open, to say so, rather than 404.
     * @param  string $token
     * @return \Illuminate\Http\Response
     */
    public function retrieve(string $token)
    {
        $participant = EventParticipant::withTrashed()
            ->where('qr_token', $token)
            ->with(['user', 'ticket', 'seat', 'purchase'])
            ->first();
        if (!$participant) {
            abort(404);
        }
        // Draft and private events are hidden from non-admins; the token is proof enough here
        $event = $participant->event()->withoutGlobalScopes()->first();
        if (!$event) {
            abort(404);
        }

        $user = Auth::user();
        if ($user && $user->banned) {
            $user = null;
        }
        if ($user && $user->admin == 1) {
            $mode = 'admin';
        } elseif ($user && $participant->user_id == $user->id) {
            $mode = 'owner';
        } else {
            $mode = 'public';
            if (!$user) {
                // Logging in from here comes back here, to see your own ticket
                Redirect::setIntendedUrl(url('/tickets/retrieve/' . $token));
            }
        }

        $now = now();
        if ($participant->trashed()) {
            $status = 'cancelled';
        } elseif ($participant->signed_in) {
            $status = 'signed_in';
        } elseif ($event->end && $now->greaterThan($event->end)) {
            $status = 'finished';
        } else {
            $status = 'valid';
        }

        $response = response(view('tickets.scan', [
            'participant' => $participant,
            'event'       => $event,
            'mode'        => $mode,
            'status'      => $status,
            'isOn'        => $event->start && $event->end && $now->between($event->start, $event->end),
            'isUpcoming'  => $event->start && $now->lessThan($event->start),
            'giftPending' => $participant->gift && !$participant->gift_accepted,
        ]));

        // Never index or cache a ticket page: what it shows depends on who's looking
        return $response->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'no-store, private');
    }
}
