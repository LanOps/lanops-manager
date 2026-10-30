<div class="table-responsive text-center">
    <table class="table">
        <thead>
            @if (!$horizontal)
                <tr>
                    @foreach ($seatingPlan->headers as $header)
                        <th class="text-center"><h4><strong>ROW {{ucwords($header)}}</strong></h4></th>
                    @endforeach
                </tr>
            @endif
         </thead>
        <tbody>
            @if (!$horizontal)
                @for ($c = $seatingPlan->columns; $c >= 1; $c--)
                    <tr>
                        @foreach ($seatingPlan->getSeatsForColumn($c)->sortBy('seat') as $seat)
                            <td>
                            
                                <button 
                                    class="btn @if ($seat->disabled) btn-danger @elseif ($seat->eventParticipant) btn-success @else btn-primary @endif btn-sm" 
                                    @if ($seat->disabled || $seatingPlan->locked) disabled @endif
                                    @if (!$seat->eventParticipant && !$seat->disabled && !$seatingPlan->locked)
                                        onclick="pickSeat(
                                            '{{ $seatingPlan->slug }}',
                                            '{{ $seat->seat }}'
                                        )"
                                        data-bs-toggle="modal"
                                        data-bs-target="#pickSeatModal_{{ $seatingPlan->slug }}"
                                    @endif
                                    >
                                    {{ $seat->seat }} - @if ($seat->eventParticipant) {{ $seat->eventParticipant->user->username }} @elseif ($seat->disabled) Disabled @else Empty @endif
                                </button>

                            </td>
                        @endforeach
                    </tr>
                @endfor
            @else
                @foreach ($seatingPlan->headers as $header)
                    <tr>
                        <td>
                            <th class="text-center"><h4><strong>ROW {{ucwords($header)}}</strong></h4></th>
                        </td>
                        @foreach ($seatingPlan->getSeatsForRow($header) as $seat)
                            <td>
                            
                                <button 
                                    class="btn @if ($seat->disabled) btn-danger @elseif ($seat->eventParticipant) btn-success @else btn-primary @endif btn-sm" 
                                    @if ($seat->disabled || $seatingPlan->locked) disabled @endif
                                    @if (!$seat->eventParticipant && !$seat->disabled && !$seatingPlan->locked)
                                        onclick="pickSeat(
                                            '{{ $seatingPlan->slug }}',
                                            '{{ $seat->seat }}'
                                        )"
                                        data-bs-toggle="modal"
                                        data-bs-target="#pickSeatModal_{{ $seatingPlan->slug }}"
                                    @endif
                                    >
                                    {{ $seat->seat }} - @if ($seat->eventParticipant) {{ $seat->eventParticipant->user->username }} @elseif ($seat->disabled) Disabled @else Empty @endif
                                </button>

                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
    @if ($seatingPlan->locked)
        <p class="text-center"><strong>NOTE: Seating Plan is currently locked!</strong></p>
    @endif
</div>

@if (!$seatingPlan->locked)
    @php
        // Only seatable tickets can be picked, so guests and people without one get told why instead
        $pickableTickets = Auth::user() ? $user->getTickets($event->id) : [];
    @endphp
    <!-- Seat Modal (one per plan, so the ids must be unique) -->
    <div class="modal fade" id="pickSeatModal_{{ $seatingPlan->slug }}" tabindex="-1" aria-labelledby="pickSeatModalLabel_{{ $seatingPlan->slug }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="pickSeatModalLabel_{{ $seatingPlan->slug }}">Pick a seat</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                @if (!Auth::user())
                    <div class="modal-body">
                        <p class="mb-0">You need to log in to pick a seat.</p>
                    </div>
                    <div class="modal-footer">
                        <a href="/login" class="btn btn-primary">Log in</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                @elseif (empty($pickableTickets))
                    <div class="modal-body">
                        <p>You need a ticket for this event to pick a seat.</p>
                        <p class="mb-0">If someone gifted you a ticket, open their gift link while logged in to claim it first.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                @else
                    {{ html()->form('POST', url('/events/' . $event->slug . '/seating/' . $seatingPlan->slug))->class('pick-seat-form')->open() }}
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="pickSeatTicket_{{ $seatingPlan->slug }}">Which ticket would you like to seat?</label>
                                {{ html()->select('participant_id', $pickableTickets)->id('pickSeatTicket_' . $seatingPlan->slug)->class('form-select') }}
                            </div>
                            <p class="mb-0">You can change or remove your seat at any time.</p>
                        </div>
                        {{ html()->hidden('user_id', $user->id) }}
                        {{ html()->hidden('seat')->class('pick-seat-input') }}
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Yes, pick this seat</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    {{ html()->form()->close() }}
                @endif
            </div>
        </div>
    </div>

    <script>
        function pickSeat(seating_plan_slug, seat)
        {
            var modal = document.getElementById('pickSeatModal_' + seating_plan_slug);
            if (!modal) {
                return;
            }
            var seatInput = modal.querySelector('.pick-seat-input');
            modal.querySelector('.modal-title').textContent = seatInput ? 'Do you want to choose seat ' + seat + '?' : 'Seat ' + seat;
            if (seatInput) {
                seatInput.value = seat;
            }
        }
    </script>
@endif
