@extends ('layouts.default')

@section ('page_title', 'Payment Details')

@section ('content')

<div class="container">
	<div class="page-header">
		<h1>
			Payment Details
		</h1> 
	</div>
	<div class="row">
		<div class="col-12 col-md-8">
			@if ($paymentGateway == 'stripe')
				
				{{ html()->form('POST', url('/payment/post'))->id('payment-form')->open() }}
					<div class="row">
						<div class="mb-3 col-sm-6 col-12">
							{{ html()->label('First Name *', 'card_first_name') }}
							{{ html()->text('card_first_name', '')->class('form-control') }}
						</div> 
						<div class="mb-3 col-sm-6 col-12">
							{{ html()->label('Last Name *', 'card_last_name') }}
							{{ html()->text('card_last_name', '')->class('form-control') }}
						</div>
					</div>
					<div class="mb-3">
						<label for="card-number-element">Card Number *</label>
						<div class="form-control" id="card-number-element"></div>
					</div>
					<div class="row">
						<div class="mb-3 col-sm-4 col-12">
							<label for="card-expiry-element">Expiry Date *</label>
							<div class="form-control" id="card-expiry-element"></div>
						</div>
						<div class="mb-3 col-sm-4 col-12">
							<label for="card-cvc-element">CVC *</label>
							<div class="form-control" id="card-cvc-element"></div>
						</div>
						<div class="mb-3 col-sm-4 col-12">
							{{ html()->label('Postcode *', 'card_postcode') }}
							{{ html()->text('card_postcode', '')->class('form-control')->attribute('autocomplete', 'postal-code') }}
						</div>
					</div>
					<div id="card-errors" class="text-danger mb-3" role="alert"></div>
					<p><small>* Required Fields</small></p>
					{{ html()->hidden('gateway', $paymentGateway) }}
					<button type="button" id="checkout_btn" class="btn btn-primary w-100">Confirm Order</button>
				</form>
			@endif
		</div>
		<div class="col-12 col-md-4">
			<div class="card">
				<div class="card-header">
					<h3 class="card-title">Order Details</h3>
				</div>
				<div class="card-body">
					@include ('layouts._partials._checkout.basket')
				</div>
			</div>
			@if ($delivery && $deliveryDetails)
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">Delivery Details</h3>
					</div>
					<div class="card-body">
						@if ($deliveryDetails && $deliveryDetails['type'] == 'shipping')
							<address>
								<strong>{{ $delivery['shipping_first_name']}} {{ $delivery['shipping_last_name'] }}</strong><br>
								{{ $delivery['shipping_address_1'] }}<br>
								@if (trim($delivery['shipping_address_2']) != '')
									{{ $delivery['shipping_address_2'] }}<br>
								@endif
								@if (trim($delivery['shipping_country']) != '')
									{{ $delivery['shipping_country'] }}<br>
								@endif
								@if (trim($delivery['shipping_state']) != '')
									{{ $delivery['shipping_state'] }}<br>
								@endif
								{{ $delivery['shipping_postcode'] }}
							</address>
						@else
							<strong>Delivery to the next event you Attend</strong>
						@endif
					</div>
				</div>
			@endif
		</div>

	</div>
</div>

@if ($paymentGateway == 'stripe')
	<script src="https://js.stripe.com/v3/"></script>
	<script>
		const stripe = Stripe( "{!! config('laravel-omnipay.gateways.stripe.credentials.public') !!}" );
		const elements = stripe.elements();

		// Stripe draws the card fields in iframes, so copy the text colours from a normal
		// Bootstrap input to make them match, in light and dark mode
		const styleSource = document.getElementById('card_first_name');
		// Stripe rejects some colour formats (such as rgb() with percentages, which the
		// Bootstrap variables use), so let the browser turn them into plain rgb()
		function resolveColor(value, fallback) {
			const probe = document.createElement('span');
			probe.style.display = 'none';
			probe.style.color = fallback;
			probe.style.color = value;
			document.body.appendChild(probe);
			const color = window.getComputedStyle(probe).color;
			probe.remove();
			return color;
		}
		function cardStyle() {
			const computed = window.getComputedStyle(styleSource);
			const root = window.getComputedStyle(document.documentElement);
			return {
				base: {
					color: resolveColor(computed.color, '#212529'),
					fontFamily: computed.fontFamily,
					fontSize: computed.fontSize,
					lineHeight: computed.lineHeight,
					'::placeholder': { color: resolveColor(root.getPropertyValue('--bs-secondary-color').trim(), '#6c757d') },
				},
				invalid: {
					color: resolveColor(root.getPropertyValue('--bs-form-invalid-color').trim(), '#dc3545'),
				},
			};
		}

		const cardNumberElement = elements.create('cardNumber', { style: cardStyle(), showIcon: true });
		const cardExpiryElement = elements.create('cardExpiry', { style: cardStyle() });
		const cardCvcElement = elements.create('cardCvc', { style: cardStyle() });
		const cardElements = [cardNumberElement, cardExpiryElement, cardCvcElement];

		cardNumberElement.mount('#card-number-element');
		cardExpiryElement.mount('#card-expiry-element');
		cardCvcElement.mount('#card-cvc-element');

		// Restyle the card fields when the colour mode is switched
		new MutationObserver(function () {
			const style = cardStyle();
			cardElements.forEach(function (element) {
				element.update({ style: style });
			});
		}).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });

		const cardErrors = document.getElementById('card-errors');
		cardElements.forEach(function (element) {
			element.on('change', function (event) {
				cardErrors.textContent = event.error ? event.error.message : '';
			});
		});

		const cardButton = document.getElementById('checkout_btn');
		const cardButtonText = cardButton.textContent;
		const form = document.getElementById('payment-form');

		function showError(message) {
			cardErrors.textContent = message;
			cardButton.disabled = false;
			cardButton.textContent = cardButtonText;
		}

		cardButton.addEventListener('click', function () {
			const firstName = document.getElementById('card_first_name').value.trim();
			const lastName = document.getElementById('card_last_name').value.trim();
			const postcode = document.getElementById('card_postcode').value.trim();
			if (firstName === '' || lastName === '') {
				showError('Please enter the first and last name on the card.');
				return;
			}
			if (postcode === '') {
				showError('Please enter the postcode of the card\'s billing address.');
				return;
			}

			cardButton.disabled = true;
			cardButton.textContent = 'Processing Please Wait...';
			cardErrors.textContent = '';

			stripe.createPaymentMethod({
				type: 'card',
				card: cardNumberElement,
				billing_details: {
					name: firstName + ' ' + lastName,
					address: { postal_code: postcode },
				},
			}).then(function (result) {
				if (result.error) {
					showError(result.error.message);
				} else {
					const hiddenInput = document.createElement('input');
					hiddenInput.setAttribute('type', 'hidden');
					hiddenInput.setAttribute('name', 'stripe_token');
					hiddenInput.setAttribute('value', result.paymentMethod.id);
					form.appendChild(hiddenInput);
					form.submit();
				}
			});
		});
	</script>

@endif

@endsection