<?php

namespace App\Http\Controllers\Events;

use Auth;
use Session;

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

        if ($user == null) {
            Session::flash('alert-danger', 'User not found.');
            return Redirect::to('/events/' . $ticket->event->slug);
        }
        // PRIVATE events are hidden from non-admins (see Event::boot), so their tickets can't be bought
        if (!$ticket->event) {
            abort(404);
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
     * Retrieve ticket via QR code
     * @param  EventParticipant $participant
     * @return Redirect
     */
    public function retrieve(EventParticipant $participant)
    {
        $user = Auth::user();
        // Event pages are found by slug, not id. Draft and private events are hidden from
        // non-admins, so look the event up without that, and let its page decide what to show
        $event = $participant->event()->withoutGlobalScopes()->first();
        if (!$event) {
            abort(404);
        }
        if ($user->admin == 1) {
            return Redirect::to('/admin/events/' . $event->slug . '/participants/' . $participant->id);
        }
        return Redirect::to('/events/' . $event->slug);
    }
}
