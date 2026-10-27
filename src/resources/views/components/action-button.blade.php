{{-- A button that sends a POST (with the CSRF token), for actions that change data --}}
@props(['action', 'method' => 'POST'])
<form method="POST" action="{{ $action }}" class="d-inline">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif
    <button type="submit" {{ $attributes->has('class') ? $attributes : $attributes->merge(['class' => 'btn btn-link p-0 align-baseline']) }}>{{ $slot }}</button>
</form>
