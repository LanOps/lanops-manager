<div class="hero-event-item">
    <span class="hero-event-type">{{ $heading }}</span>
    <h2 class="hero-event-name"><a href="/events/{{ $event->slug }}" class="hero-event-link">{{ $event->display_name }}</a></h2>
    <p class="hero-event-date">
        {{ date('jS', strtotime($event->start)) }} &ndash; {{ date('jS F Y', strtotime($event->end)) }}
    </p>
    <div class="hero-countdown" data-countdown="{{ \Carbon\Carbon::parse($event->start)->toIso8601String() }}">
        <div class="hero-countdown-unit"><span class="hero-countdown-value" data-unit="d">--</span><span class="hero-countdown-label">days</span></div>
        <div class="hero-countdown-unit"><span class="hero-countdown-value" data-unit="h">--</span><span class="hero-countdown-label">hrs</span></div>
        <div class="hero-countdown-unit"><span class="hero-countdown-value" data-unit="m">--</span><span class="hero-countdown-label">mins</span></div>
        <div class="hero-countdown-unit"><span class="hero-countdown-value" data-unit="s">--</span><span class="hero-countdown-label">secs</span></div>
    </div>
    @if ($event->venue)
        @php
            $v = $event->venue;
            $addr = implode(', ', array_filter([$v->address_1, $v->address_2, $v->address_street, $v->address_city, $v->address_postcode]));
            $mapsQuery = urlencode(implode(', ', array_filter([$v->display_name, $addr])));
        @endphp
        <p class="hero-event-venue">
            <a href="https://www.google.com/maps/search/?api=1&query={{ $mapsQuery }}" target="_blank" rel="noopener" class="hero-venue-link">
                {{ $v->display_name }}@if($addr)<br><span class="hero-event-address">{{ $addr }}</span>@endif
            </a>
        </p>
    @endif
    <a href="/events/{{ $event->slug }}#tickets" class="btn btn-orange btn-lg hero-cta">Get Your Ticket</a>
</div>
