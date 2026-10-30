<?php

namespace App\Http\Controllers\Admin;

use Auth;
use Helpers;
use \Carbon\Carbon as Carbon;

use App\Models\User;
use App\Models\Event;
use App\Models\Poll;
use App\Models\PollOptionVote;
use App\Models\EventParticipant;
use App\Models\NewsComment;

use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    /**
     * Show Admin Index Page
     * @return view
     */
    public function index()
    {
        $user = Auth::user();
        $participants = EventParticipant::getNewParticipants('login');
        $votes = PollOptionVote::getNewVotes('login');
        $comments = NewsComment::getNewComments('login');
        // Polls with no end date, or one still in the future
        $activePolls = Poll::whereNull('end')->orWhere('end', '>=', Carbon::now())->get();
        $userLastLoggedIn = User::where('id', '!=', Auth::id())->latest('last_login')->first();
        $ticketBreakdown = array();
        // Tickets from the last 12 months, by month
        $lastYear = EventParticipant::where('created_at', '>=', Carbon::now()->subMonths(12))->orderBy('created_at')->get(['created_at']);
        foreach ($lastYear as $participant) {
            $ticketBreakdown[date_format($participant->created_at, 'Y-m')][] = $participant;
        }
        return view('admin.index')
            ->withUser($user)
            ->withEventCount(Event::count())
            ->withParticipants($participants)
            ->withVotes($votes)
            ->withComments($comments)
            ->withActivePolls($activePolls)
            ->withUserLastLoggedIn($userLastLoggedIn)
            ->withUserCount(User::count())
            ->withParticipantCount(EventParticipant::count())
            ->withNextEvent(Helpers::getNextEventName())
            ->withTicketBreakdown($ticketBreakdown);
    }
}
