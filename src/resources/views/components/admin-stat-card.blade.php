{{--
	A stat card for the admin dashboards: an icon, a big number and a link.
	The number never wraps; it shrinks with the card instead (see .stat-card
	in admin.scss). Cards in the same row stretch to the same height.
--}}
@props(['icon', 'value', 'label', 'href', 'linkText' => 'View Details', 'colour' => null])
@php
	$value = (string) $value;
@endphp
<div {{ $attributes->class(['card', 'stat-card', 'h-100', 'mb-0', 'border-' . $colour => $colour]) }}>
	<div @class(['card-header', 'text-bg-' . $colour => $colour])>
		<i class="fa {{ $icon }} stat-card-icon" aria-hidden="true"></i>
		<div class="stat-card-text">
			<div class="huge stat-card-value" style="--stat-length: {{ max(mb_strlen($value), 1) }}" title="{{ $value }}">{{ $value }}</div>
			<div>{{ $label }}</div>
		</div>
	</div>
	<a href="{{ $href }}">
		<div class="card-footer">
			<span class="float-start">{{ $linkText }}</span>
			<span class="float-end"><i class="fa fa-circle-arrow-right"></i></span>
			<div class="clearfix"></div>
		</div>
	</a>
</div>
