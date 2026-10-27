{{--
	A user's avatar. Falls back to the default image when the user has no
	avatar, and again in the browser when the avatar URL fails to load
	(dead Steam or Discord links).

	The prop is "member", not "user": AppServiceProvider shares the logged-in
	user into every view as $user, which would replace it.
--}}
@props(['member' => null, 'src' => null])
@php
	$fallback = asset('images/default-avatar.svg');
	$src = $src ?: ($member ? $member->getAvatarUrl() : $fallback);
@endphp
<img src="{{ $src }}" onerror="this.onerror=null;this.src='{{ $fallback }}';" {{ $attributes->merge(['alt' => ($member ? $member->username . "'s " : '') . 'Avatar']) }}>
