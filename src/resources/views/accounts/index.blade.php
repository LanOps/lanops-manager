@extends ('layouts.default')

@section ('page_title', 'Updating your Profile')

@section ('content')

	<div class="container">

		<div class="page-header">
			<h1>
				My Account
			</h1>
		</div>
		<div class="row">
			<!-- ACCOUNT DETAILS -->
			<div class="col-12 col-lg-12">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">Account Details</h3>
					</div>
					<div class="card-body">
						{{ html()->form('POST', url('/account/'))->open() }}
							<div class="row" style="display: flex; align-items: center;">
								<div class="col-md-2 col-sm-12">
									@if ($user->avatar != NULL)
										<img src="{{ $user->avatar }}" alt="{{ $user->username }}'s Avatar" class="img-fluid img-thumbnail"/>
									@endif
								</div> 
								<div class="col-md-10 col-sm-12">
					                <div class="row">
					                    <div class="col-12 col-md-6">
					                        <div class="mb-3 @error('firstname') @enderror">
					                            {{ html()->label('Firstname', 'firstname') }}
					                            <input id="firstname" type="firstname" class="form-control" name="firstname" value="{{ $user->firstname }}" required autocomplete="firstname">
					                        </div>
					                    </div>
					                    <div class="col-12 col-md-6">
					                        <div class="mb-3 @error('surname') @enderror">
					                            {{ html()->label('Surname', 'surname') }}
					                            <input id="surname" type="surname" class="form-control" name="surname" value="{{ $user->surname }}" required autocomplete="surname">
					                        </div>
					                    </div>
					                </div>
									<div class="mb-3">
										{{ html()->label('User Name', 'Username') }}
										{{ html()->text('name', $user->username)->class('form-control')->disabled() }}
									</div> 
									@if ($steamProfile = Helpers::getSteamProfile($user->steamid))
										<div class="mb-3">
											{{ html()->label('Linked Steam Account', 'steamname') }}
											{{ html()->text('steamname', $steamProfile["name"])->class('form-control')->disabled() }}
										</div>
									@endif
									@if ($user->email)
										<div class="mb-3">
											{{ html()->label('Email', 'email') }}
											<input type="email" class="form-control" name="email" id="email @error('email') is-invalid @enderror" aria-describedby="email" value="{{ $user->email }}" placeholder="Enter email">
											@error('email')
				                                <span class="invalid-feedback" role="alert">
				                                    <strong>{{ $message }}</strong>
				                                </span>
				                            @enderror
										</div>
									@endif
									@if ($user->password)
										<div class="mb-3">
											<label for="password1">Change Password</label>
											<input type="password" name="password1" class="form-control @error('password1') is-invalid @enderror" id="password1" placeholder="Password">
										 	@error('password1')
				                                <span class="invalid-feedback" role="alert">
				                                    <strong>{{ $message }}</strong>
				                                </span>
				                            @enderror
										</div>
										<div class="mb-3">
											<label for="password2">Confirm Password</label>
											<input type="password" name="password2" class="form-control @error('password2') is-invalid @enderror" id="password2" placeholder="Password">
										 	@error('password2')
				                                <span class="invalid-feedback" role="alert">
				                                    <strong>{{ $message }}</strong>
				                                </span>
				                            @enderror
										</div>
									@endif
									<button type="submit" class="btn btn-primary w-100">Submit</button>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>

			<div class="col-12">
				<div class="row">
					<!-- DISCORD --> 
					<div class="col-12 col-md-6">
						<div class="card">
							<div class="card-header">
								<h3 class="card-title">Discord Link</h3>
							</div>
							<div class="card-body">
								<div class="row" style="display: flex; align-items: center;">
									@if (is_null($user->discord_id))
										<a class="btn btn-lg btn-discord w-100" href="{{ $discordLinkUrl }}">
											<button class="btn btn-success w-100"> 
												Link Account
											</button>
										</a>
									@else
										<div class="col-12 col-md-2">
											<img alt="{{ $user->username }}'s Discord Avatar" class="img-fluid img-thumbnail" src="https://cdn.discordapp.com/avatars/{{ $user->discord_id }}/{{ $user->getFormattedDiscordAvatar() }}" />
										</div>
										<div class="col-12 col-md-10">
											{{ html()->form('POST', url('/account/discord/unlink'))->open() }}
												<button  type="submit"  class="btn btn-danger w-100"> 
													Unlink Account <strong>{{ $user->discord_username }}</strong>
												</button>
											</form>
										</div>
									@endif
								</div>
							</div>
						</div>
					</div>

					<!-- REFER A FRIEND -->
					<div class="col-12 col-md-6">
						<div class="card">
							<div class="card-header">
								<h3 class="card-title">Refer a Friend</h3>
							</div>
							<div class="card-body">
									<p>You have <strong>{{ $user->getReferralsUnclaimedCount() }}</strong> Referrals.@if ( $user->getReferralsUnclaimedCount() ) A discount will be applied to your purchase!@endif</p>
									<p>You have used <strong>{{ $user->getReferralsRedeemedCount() }}</strong> Referrals</p>
								@if (App\Models\User::isValidReferralCode($user->referral_code))
									<p>Give your friends this referral code and get {{ config('app.currency_symbol') }}{{ config('app.refer_a_friend_discount') }} off your next purchase!</p>
									<div class="alert alert-info">
										<p>{{ $user->referral_code }}</p>
									</div>
								@else
									<div class="alert alert-info">
										Your Referral Code will unlock when you have purchased a ticket!
									</div>
								@endif
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<!-- TICKETS -->
			<div class="col-12">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">Tickets</h3>
					</div>
					<div class="card-body">
						@if (count($eventParticipants))
							@foreach ($eventParticipants as $participant)
								@include ('layouts._partials._tickets.index')
							@endforeach
							{{ $eventParticipants->links() }}
						@else
							You currently have no tickets.
						@endif
					</div>
				</div>
			</div>

			<!-- PURCHASES -->
			<div class="col-12">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">Purchases</h3>
					</div>
					<div class="card-body">
						@if (count($user->purchases))
							<table class="table table-striped">
								<thead>
									<tr>
										<th>
											ID
										</th>
										<th>
											Method
										</th>
										<th>
											Time
										</th>
										<th>
											Basket
										</th>
									</tr>
								</thead>
								<tbody>
									@foreach ($purchases as $purchase)
										<tr>
											<td>
												{{ $purchase->id }}
											</td>
											<td>
												{{ $purchase->getPurchaseType() }}
											</td>
											<td>
												{{  date('d-m-y H:i', strtotime($purchase->created_at)) }}
											</td>
											<td>
												<div class="card">
													<div class="card-body">
														@if ($purchase->basket)
															@include ('layouts._partials._checkout.basket', ['basket' => Helpers::formatBasket($purchase->basket, $purchase->user, $purchase->referral_discount_total, true)])
														@elseif (!$purchase->participants->isEmpty())
															@foreach ($purchase->participants as $participant)
																{{ $participant->event->display_name }} - {{ $participant->ticket->name }}
																@if (!$loop->last)
																	<hr>
																@endif
															@endforeach
														@endif
													</div>
												</div>
											</td>
										</tr>
									@endforeach
								</tbody>
							</table>
							{{ $purchases->links() }}
						@else
							You have no purchases
						@endif
					</div>
				</div>
			</div>

			<!-- DANGER ZONE -->
			{{-- <div class="col-sm-12 col-12 col-md-12 col-lg-12">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">Danger Zone</h3>
					</div>
					<div class="card-body">
						<button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">Delete Account</button>
					</div>
				</div>
			</div> --}}
		</div>
		@include ('layouts._partials._gifts.modal')
	</div>

	<!-- Confirm Delete Modal -->
	{{-- <div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="confirmDeleteModalLabel">Are you sure you want to Delete your Account?</h4>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				{{ html()->form('POST', url('/account/delete/'))->id('confirmDeleteFormModal')->open() }}
					<div class="modal-body">
						<div class="mb-3">
							<p>Once it's gone... It's gone, puff... Aaaannnd it's gone!</p>
							<p><strong>All</strong> user records will be deleted.</p>
							<p><strong>All</strong> tickets for upcoming events will be forfeit.</p>
							<p><strong>All</strong> tickets gifted to you for upcoming will be forfeit.</p> 
							<p>Tickets gifted to you will <strong>not</strong> be transferred back to the gifter</p>
							<p>Refunds will <strong>not</strong> be given for any forfeit tickets.</p>
							<p><strong>By clicking Accept you are agreeing to these terms.</strong></p>
						</div>
					</div>
					<div class="modal-footer">
						<button type="submit" class="btn btn-success">Accept</button>
						<button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
					</div>
				</form>
			</div>
		</div>
	</div> --}}
	
@endsection