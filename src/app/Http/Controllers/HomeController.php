<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

use App\Models\Event;
use App\Models\NewsArticle;

class HomeController extends Controller
{
    /**
     * Show Index Page
     * @return View
     */
    public function index()
    {
        $sliderDir = public_path('videos/slider/');
        $sliderFiles = glob($sliderDir . '*.{mp4,webm}', GLOB_BRACE) ?: [];
        $sliderVideos = array_map(
            fn($f) => '/videos/slider/' . basename($f),
            $sliderFiles
        );

        // The next listed event of each type, in home page order; types with nothing coming up are left out
        $nextEvents = [];
        foreach (array_keys(Event::getNextEventHeadings()) as $type) {
            $next = Event::listed()->where('end', '>=', \Carbon\Carbon::now())
                ->where('type', $type)
                ->orderBy(DB::raw('ABS(DATEDIFF(events.end, NOW()))'))
                ->first();
            if ($next) {
                $nextEvents[$type] = $next;
            }
        }
        $nextLan = $nextEvents[Event::$typeLan] ?? null;

        $seoTitle       = config('app.tagline');
        $seoDescription = config('app.seo_description');

        if ($nextLan) {
            $eventDate      = date('jS F Y', strtotime($nextLan->start));
            $seoTitle       = 'Next LAN: ' . $nextLan->display_name . ' — ' . $eventDate;
            $seoDescription = 'Join us for ' . $nextLan->display_name . ' on ' . $eventDate
                . ($nextLan->venue ? ' at ' . $nextLan->venue->display_name : '')
                . '. ' . config('app.seo_description');
        }

        seo()
            ->title($seoTitle)
            ->description($seoDescription)
            ->url(config('app.url') . '/')
            ->image(config('app.url') . '/images/og-image.jpg');

        return view("home")
            ->withNextEvent(\App\Libraries\Helpers::getNextEvent())
            ->withNextEvents($nextEvents)
            ->withNewsArticles(NewsArticle::limit(4)->orderBy('created_at', 'desc')->get())
            // Only upcoming events are shown in the calendar
            ->withEvents(Event::listed()->where('start', '>', \Carbon\Carbon::today())->orderBy('created_at', 'DESC')->get())
            ->withSliderVideos($sliderVideos)
        ;
    }
    
    /**
     * Show About us Page
     * @return View
     */
    public function about()
    {
        return view("about");
    }
    
    /**
     * Show Terms and Conditions Page
     * @return View
     */
    public function terms()
    {
        return view("terms");
    }

    /**
     * Show Contact Page
     * @return View
     */
    public function contact()
    {
        return view("contact");
    }

    /**
     * Show Information Page
     * @return View
     */
    public function info()
    {
        return view("info");
    }
}
