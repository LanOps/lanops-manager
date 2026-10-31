@php
	$refundable = $purchase->getRefundableInPence() / 100;
	$currency = config('app.currency_symbol');
@endphp
<div class="card mt-3" id="refund">
	<div class="card-header">
		<i class="fa fa-undo fa-fw"></i> Refund
	</div>
	<div class="card-body">
		@if ($purchase->allParticipants->where('deleted_at', null)->isEmpty() && $refundable <= 0)
			<p class="mb-0">Nothing left to refund or cancel on this purchase.</p>
		@else
			@if (!$purchase->isStripe())
				<div class="alert alert-warning">
					This was a {{ $purchase->getPurchaseType() }} payment. The site can only refund card payments, so a refund here is <strong>only recorded</strong>: send the money back by hand.
				</div>
			@endif
			{{ html()->form('POST', url('/admin/purchases/' . $purchase->id . '/refund'))->id('refund-form')->open() }}
				<div class="table-responsive">
					<table class="table table-sm align-middle">
						<thead>
							<tr>
								<th></th>
								<th>Ticket</th>
								<th>Holder</th>
								<th>Seat</th>
								<th class="text-end">Price</th>
							</tr>
						</thead>
						<tbody>
							@foreach ($purchase->allParticipants as $participant)
								<tr @if ($participant->trashed()) class="text-body-secondary" @endif>
									<td>
										@if ($participant->trashed())
											<span class="badge text-bg-secondary">Cancelled</span>
										@elseif ($participant->signed_in)
											<span class="badge text-bg-info" title="Signed in tickets can't be cancelled">Signed in</span>
										@else
											<input class="form-check-input refund-ticket" type="checkbox" name="participants[]" value="{{ $participant->id }}" id="refund-participant-{{ $participant->id }}" data-price="{{ (float) ($participant->ticket->price ?? 0) }}">
										@endif
									</td>
									<td>
										<label for="refund-participant-{{ $participant->id }}">
											{{ $participant->ticket->name ?? 'Ticket' }}
											<small class="text-body-secondary d-block">{{ $participant->event->display_name ?? '' }}</small>
										</label>
									</td>
									<td>{{ $participant->user->username ?? '' }}</td>
									<td>{{ $participant->trashed() ? '' : ($participant->seat->seat ?? '') }}</td>
									<td class="text-end">{{ $currency }}{{ number_format((float) ($participant->ticket->price ?? 0), 2) }}</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
				<div class="row g-3">
					<div class="col-sm-6">
						{{ html()->label('Amount to refund', 'refund-amount')->class('form-label') }}
						<div class="input-group">
							<span class="input-group-text">{{ $currency }}</span>
							<input type="number" class="form-control" name="amount" id="refund-amount" min="0" max="{{ number_format($refundable, 2, '.', '') }}" step="0.01" value="0.00" required data-refundable="{{ number_format($refundable, 2, '.', '') }}">
						</div>
						<div class="form-text">
							{{ $currency }}{{ number_format($refundable, 2) }} left to refund. Fills in from the tickets you tick; lower it for a partial refund, or 0 to cancel without a refund.
						</div>
					</div>
					<div class="col-sm-6">
						{{ html()->label('Reason (optional)', 'refund-reason')->class('form-label') }}
						<input type="text" class="form-control" name="reason" id="refund-reason" maxlength="255">
					</div>
					<div class="col-12">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="cancel_tickets" value="1" id="refund-cancel" checked>
							<label class="form-check-label" for="refund-cancel">
								Cancel the ticked tickets (frees the seat, stops the QR code, puts the place back on sale)
							</label>
						</div>
					</div>
					<div class="col-12">
						<button type="submit" class="btn btn-danger">Refund</button>
					</div>
				</div>
			</form>
		@endif

		@if ($purchase->refunds->isNotEmpty())
			<h6 class="mt-4">Refund history</h6>
			<div class="table-responsive">
				<table class="table table-sm mb-0">
					<thead>
						<tr>
							<th>When</th>
							<th>By</th>
							<th class="text-end">Amount</th>
							<th>How</th>
							<th>Tickets cancelled</th>
							<th>Reason</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($purchase->refunds as $refund)
							<tr>
								<td>{{ $refund->created_at }}</td>
								<td>{{ $refund->refundedBy->username ?? '' }}</td>
								<td class="text-end">{{ $currency }}{{ number_format((float) $refund->amount, 2) }}</td>
								<td>
									@if ($refund->wentThroughGateway())
										Stripe <small class="text-body-secondary">{{ $refund->gateway_refund_id }} ({{ $refund->status }})</small>
									@elseif ($refund->amount > 0)
										Recorded by hand
									@else
										No refund
									@endif
								</td>
								<td>{{ $refund->participants->count() }}</td>
								<td>{{ $refund->reason }}</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		@endif
	</div>
</div>
<script>
	(function () {
		var form = document.getElementById('refund-form');
		if (!form) {
			return;
		}
		var amount = document.getElementById('refund-amount');
		var cancel = document.getElementById('refund-cancel');
		var refundable = parseFloat(amount.dataset.refundable);
		form.querySelectorAll('.refund-ticket').forEach(function (box) {
			box.addEventListener('change', function () {
				var total = 0;
				form.querySelectorAll('.refund-ticket:checked').forEach(function (checked) {
					total += parseFloat(checked.dataset.price) || 0;
				});
				amount.value = Math.min(total, refundable).toFixed(2);
			});
		});
		form.addEventListener('submit', function (e) {
			var tickets = form.querySelectorAll('.refund-ticket:checked').length;
			var parts = [];
			if (parseFloat(amount.value) > 0) {
				parts.push('refund {{ $currency }}' + parseFloat(amount.value).toFixed(2) + ' {{ $purchase->isStripe() ? 'to the card' : '(recorded only)' }}');
			}
			if (cancel.checked && tickets > 0) {
				parts.push('cancel ' + tickets + (tickets === 1 ? ' ticket' : ' tickets'));
			}
			if (parts.length && !confirm('This will ' + parts.join(' and ') + '. This can\'t be undone. Continue?')) {
				e.preventDefault();
			}
		});
	})();
</script>
