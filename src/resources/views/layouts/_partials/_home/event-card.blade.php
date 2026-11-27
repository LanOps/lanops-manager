@php
    $capacity  = count($event->seatingPlans) > 0
        ? $event->getSeatingCapacity()
        : $event->capacity;
    $filled    = $event->eventParticipants->count();
    $remaining = max($capacity - $filled, 0);
    $pct       = $capacity > 0 ? round(($filled / $capacity) * 100) : 0;
    $colClass  = match (true) {
        $columns >= 3 => 'col-12 col-md-6 col-lg-4',
        $columns == 2 => 'col-12 col-md-6',
        default       => 'col-12',
    };
@endphp
<div class="{{ $colClass }}">
    <div class="event-card">
        <div class="event-card-header">
            <span class="event-card-badge {{ $event->getTypeBadgeVariant() ? 'event-card-badge--' . $event->getTypeBadgeVariant() : '' }}">{{ $event->type }}</span>
            <h3 class="event-card-title">{{ $event->display_name }}</h3>
        </div>
        <div class="event-card-body">
            <dl class="event-card-meta">
                <dt>When</dt>
                <dd>{{ date('jS', strtotime($event->start)) }} &ndash; {{ date('jS F Y', strtotime($event->end)) }}</dd>
                @if ($event->venue)
                    @php
                        $v = $event->venue;
                        $addr = implode(', ', array_filter([$v->address_1, $v->address_2, $v->address_street, $v->address_city, $v->address_postcode]));
                        $mapsQuery = urlencode(implode(', ', array_filter([$v->display_name, $addr])));
                    @endphp
                    <dt>Where</dt>
                    <dd><a href="https://www.google.com/maps/search/?api=1&query={{ $mapsQuery }}" target="_blank" rel="noopener">{{ $v->display_name }}@if($addr)<br>{{ $addr }}@endif</a></dd>
                @endif
                @if ($event->tickets && $event->tickets->count())
                    <dt>From</dt>
                    <dd>{{ config('app.currency_symbol') }}{{ $event->getCheapestTicket() }}</dd>
                @endif
            </dl>
            @if ($event->desc_short)
                <div class="event-card-desc">{!! $event->desc_short !!}</div>
            @endif
            <div class="capacity-wrap">
                <div class="capacity-bar">
                    <div class="capacity-fill" style="width: {{ $pct }}%"></div>
                </div>
                <span class="capacity-text">{{ $remaining }} of {{ $capacity }} tickets remaining</span>
            </div>
        </div>
        <div class="event-card-footer">
            <a href="/events/{{ $event->slug }}" class="btn btn-outline-secondary btn-sm">Details</a>
            <a href="/events/{{ $event->slug }}#tickets" class="btn btn-orange btn-sm">Book Now</a>
        </div>
    </div>
</div>
