{{--
	A user's avatar. Falls back to the default image when the user has no
	avatar, and again in the browser when the avatar URL fails to load
	(dead Steam or Discord links).
--}}
@props(['user' => null, 'src' => null])
@php
	$fallback = asset('images/default-avatar.svg');
	$src = $src ?: ($user ? $user->getAvatarUrl() : $fallback);
@endphp
<img src="{{ $src }}" onerror="this.onerror=null;this.src='{{ $fallback }}';" {{ $attributes->merge(['alt' => ($user ? $user->username . "'s " : '') . 'Avatar']) }}>
