@extends ('layouts.default')

@section ('content')

@php
    $headings = \App\Models\Event::getNextEventHeadings();
@endphp

{{-- On now: live events have left the Next slots, so they get a bar of their own at the top --}}
@foreach ($liveEvents as $liveEvent)
    <div class="live-event-bar">
        <div class="container live-event-bar-inner">
            <span class="live-event-bar-badge"><span class="live-event-bar-dot" aria-hidden="true"></span>On Now</span>
            <span class="live-event-bar-name">
                <a href="/events/{{ $liveEvent->slug }}">{{ $liveEvent->display_name }}</a>
                <span class="live-event-bar-until">until {{ date('jS F', strtotime($liveEvent->end)) }}</span>
            </span>
            <span class="live-event-bar-links">
                <a href="/events/{{ $liveEvent->slug }}#timetable" class="btn btn-sm btn-outline-light">Timetable</a>
                <a href="/events/{{ $liveEvent->slug }}" class="btn btn-sm btn-orange">View event</a>
            </span>
        </div>
    </div>
@endforeach

{{-- Hero --}}
<div id="hero-section" class="hero-section">
    <video id="hero-vid-a" class="hero-slide-video hero-slide-video--active" muted playsinline autoplay preload="auto"></video>
    <video id="hero-vid-b" class="hero-slide-video" muted playsinline preload="none"></video>
    <div class="hero-content">
        <div class="hero-event-block">
            <div class="hero-events-grid">
                @foreach ($nextEvents as $type => $nextEvent)
                    @if (!$loop->first)
                        <div class="hero-event-col-divider"></div>
                    @endif
                    @include ('layouts._partials._home.hero-event', ['event' => $nextEvent, 'heading' => $headings[$type]])
                @endforeach

                @if (empty($nextEvents))
                    <div class="hero-event-item">
                        <span class="hero-event-type">Gaming Events</span>
                        <h2 class="hero-event-name">Coming Soon</h2>
                        <p class="hero-event-date">Stay tuned for our next event announcement</p>
                        <a href="/news" class="btn btn-orange btn-lg hero-cta">Read the News</a>
                    </div>
                @endif
            </div>
        </div>
        <hr class="hero-divider">
        <div class="hero-bio">
            @include ('layouts._partials._about.short')
        </div>
    </div>
</div>

{{-- Upcoming Events --}}
@if (!empty($nextEvents))
<div class="featured-events section-padding">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2 class="section-heading">Upcoming Events</h2>
            </div>
        </div>
        <div class="row">

            @foreach ($nextEvents as $nextEvent)
                @include ('layouts._partials._home.event-card', ['event' => $nextEvent, 'columns' => count($nextEvents)])
            @endforeach

        </div>
    </div>
</div>
@endif

{{-- Discord CTA --}}
@if (config('app.discord_link'))
<div class="discord-cta">
    <div class="discord-cta-inner">
        <div class="discord-cta-left">
            <svg class="discord-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 127.14 96.36" aria-hidden="true">
                <path fill="#fff" d="M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.83A97.68,97.68,0,0,0,49,6.83,72.37,72.37,0,0,0,45.64,0,105.89,105.89,0,0,0,19.39,8.09C2.79,32.65-1.71,56.6.54,80.21h0A105.73,105.73,0,0,0,32.71,96.36,77.7,77.7,0,0,0,39.6,85.25a68.42,68.42,0,0,1-10.85-5.18c.91-.66,1.8-1.34,2.66-2a75.57,75.57,0,0,0,64.32,0c.87.71,1.76,1.39,2.66,2a68.68,68.68,0,0,1-10.87,5.19,77,77,0,0,0,6.89,11.1A105.25,105.25,0,0,0,126.6,80.22h0C129.24,52.84,122.09,29.11,107.7,8.07ZM42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.89,53,48.84,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.25,60,73.25,53s5-12.74,11.44-12.74S96.23,46,96.12,53,91.08,65.69,84.69,65.69Z"/>
            </svg>
            <div class="discord-cta-text">
                <h2>Join us on Discord</h2>
                <p>Chat, stay up to date with events, and connect with the community between LANs.</p>
            </div>
        </div>
        <a href="{{ config('app.discord_link') }}" target="_blank" rel="noopener" class="btn discord-cta-btn">Join the Server</a>
    </div>
</div>
@endif

{{-- About + Event Calendar --}}
<div class="about-calendar-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-7">
                <h2 class="section-heading">About {{ config('app.name') }}</h2>
                @include ('layouts._partials._about.short')
                <a href="/about" class="btn btn-outline-secondary" style="margin-top:10px;">Learn More</a>
            </div>
            <div class="col-12 col-md-5">
                <h2 class="section-heading" style="margin-top:30px;">Event Calendar</h2>
                @php $futureEvents = $events->filter(fn($e) => $e->start > \Carbon\Carbon::now()); @endphp
                @if ($futureEvents->count() > 0)
                    <ul class="cal-list">
                        @foreach ($futureEvents as $calEvent)
                            <li class="cal-item">
                                <a href="/events/{{ $calEvent->slug }}" class="cal-name">
                                    {{ $calEvent->display_name }}
                                    @if ($calEvent->status !== 'PUBLISHED')
                                        <span class="badge text-bg-secondary" style="font-size:10px;">{{ $calEvent->status }}</span>
                                    @endif
                                </a>
                                <span class="cal-date">
                                    {{ date('jS', strtotime($calEvent->start)) }} &ndash; {{ date('jS F Y', strtotime($calEvent->end)) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted">No upcoming events — check back soon.</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Latest News --}}
<div class="news-grid-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2 class="section-heading">Latest News</h2>
            </div>
        </div>
        @if (!$newsArticles->isEmpty())
            <div class="row">
                @foreach ($newsArticles as $newsArticle)
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="news-card">
                            <div class="news-card-body">
                                <h4 class="news-card-title">
                                    <a href="/news/{{ $newsArticle->slug }}">{{ $newsArticle->title }}</a>
                                </h4>
                                <p class="news-card-excerpt">{{ $newsArticle->getExcerpt() }}</p>
                            </div>
                            <div class="news-card-foot">
                                <span class="news-card-date">{{ date('M j, Y', strtotime($newsArticle->created_at)) }}</span>
                                <a href="/news/{{ $newsArticle->slug }}" class="news-card-more">Read more &rarr;</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="row">
                <div class="col-12 text-center" style="margin-top:20px;">
                    <a href="/news" class="btn btn-outline-secondary">All News</a>
                </div>
            </div>
        @else
            <p class="text-muted">Nothing here yet...</p>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    var clips = @json($sliderVideos);
    if (!clips.length) return;

    // Fisher-Yates shuffle so order is random on every page load
    for (var i = clips.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var t = clips[i]; clips[i] = clips[j]; clips[j] = t;
    }

    var vidA   = document.getElementById('hero-vid-a');
    var vidB   = document.getElementById('hero-vid-b');
    var vids   = [vidA, vidB];
    var active = 0;
    var idx    = 0;

    function startKenBurns(el) {
        el.classList.remove('hero-slide-video--zoom');
        void el.offsetWidth; // force reflow to restart animation
        el.classList.add('hero-slide-video--zoom');
    }

    function activate(el) {
        el.classList.add('hero-slide-video--active');
        startKenBurns(el);
    }

    function deactivate(el) {
        el.classList.remove('hero-slide-video--active');
        el.classList.remove('hero-slide-video--zoom');
    }

    function preloadNext() {
        var nextIdx = (idx + 1) % clips.length;
        var standby = vids[1 - active];
        standby.src     = clips[nextIdx];
        standby.preload = 'auto';
        standby.load();
    }

    function switchToNext() {
        idx = (idx + 1) % clips.length;
        var nextActive = 1 - active;
        var incoming   = vids[nextActive];
        var outgoing   = vids[active];

        function doSwitch() {
            incoming.play().catch(function(){});
            activate(incoming);
            deactivate(outgoing);
            active = nextActive;
            preloadNext();
        }

        // Wait for the next clip to be buffered enough before fading in
        if (incoming.readyState >= 3) {
            doSwitch();
        } else {
            incoming.addEventListener('canplay', doSwitch, { once: true });
        }
    }

    // Skip any clips that fail to load (e.g. deleted files)
    vidA.addEventListener('error', function () { if (active === 0) switchToNext(); });
    vidB.addEventListener('error', function () { if (active === 1) switchToNext(); });

    vidA.addEventListener('ended', switchToNext);
    vidB.addEventListener('ended', switchToNext);

    // Boot first clip
    vidA.src = clips[0];
    vidA.load();
    vidA.play().catch(function(){});
    activate(vidA);
    preloadNext();
}());
</script>
<script>
(function () {
    var countdowns = document.querySelectorAll('.hero-countdown[data-countdown]');
    if (!countdowns.length) return;

    function tick() {
        var now = Date.now();
        countdowns.forEach(function (el) {
            var target = new Date(el.getAttribute('data-countdown')).getTime();
            var diff = target - now;
            if (diff <= 0) {
                el.querySelectorAll('.hero-countdown-value').forEach(function (v) { v.textContent = '0'; });
                return;
            }
            var d = Math.floor(diff / 86400000);
            var h = Math.floor((diff % 86400000) / 3600000);
            var m = Math.floor((diff % 3600000) / 60000);
            var s = Math.floor((diff % 60000) / 1000);
            el.querySelector('[data-unit="d"]').textContent = d;
            el.querySelector('[data-unit="h"]').textContent = String(h).padStart(2, '0');
            el.querySelector('[data-unit="m"]').textContent = String(m).padStart(2, '0');
            el.querySelector('[data-unit="s"]').textContent = String(s).padStart(2, '0');
        });
    }

    tick();
    setInterval(tick, 1000);
}());
</script>
@endpush

@endsection
