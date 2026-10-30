@extends ('layouts.admin-default')

@section ('page_title', 'Users - View '. $userShow->username)

@section ('content')
<div class="row">
	<div class="col-lg-12">
		<h3 class="pb-2 mb-3 border-bottom">{{ $userShow->username }}</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item">
				<a href="/admin/users/">Users</a>
			</li>
			<li class="breadcrumb-item active">
				{{ $userShow->username }}
			</li>
		</ol> 
	</div>
</div>

<div class="row">
	<div class="col-sm-12 col-lg-10">
		<div class="card">
			<div class="card-header">
				<i class="fa fa-users fa-fw"></i> User
			</div>
			<div class="card-body">
				@if ($userShow->banned)
					<div class="alert alert-danger">This User has been banned!</div>
				@endif
				<div class="d-flex">
  					<div class="flex-shrink-0 me-3">
						<x-avatar :member="$userShow" />
			  		</div>
  					<div class="flex-grow-1">
						<ul class="list-group">
							<li class="list-group-item">Username: {{ $userShow->username }}</li>
							@if ($steamProfile = Helpers::getSteamProfile($userShow->steamid)) <li class="list-group-item">Steam: {{ $steamProfile["name"] }}</li> @endif
							<li class="list-group-item">Name: {{ $userShow->firstname }} {{ $userShow->surname }}</li>
							<li class="list-group-item">
								Admin: @if ($userShow->admin) Yes @else No @endif
							</li>
							@if ($userShow->email != null)
								<li class="list-group-item">Email: {{ $userShow->email }}</li>
							@endif
							<li class="list-group-item">Referral Discounts Unclaimed: {{ $userShow->getReferralsUnclaimedCount() }}</li>
							<li class="list-group-item">Referral Discounts Used: {{ $userShow->getReferralsRedeemedCount() }}</li>
						</ul>
  					</div>
  				</div>
			</div>  
		</div>
		<div class="card">
			<div class="card-header">
				<h3 class="card-title">Purchases</h3>
			</div>
			<div class="card-body">
				@if (count($userShow->purchases))
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
								<th>
									Referral Code
								</th>
								<th>
								</th>
							</tr>
						</thead>
						<tbody>
							@foreach ($userShow->purchases as $purchase)
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
									</td>
									<td>
										@if ($purchase->referralUser )
											<a href="/admin/users/{{ $purchase->referralUser->id }}">{{ $purchase->referralUser->referral_code }} - {{ $purchase->referralUser->username }}</a>
										@endif
									</td>
									<td>
										<a href="/admin/purchases/{{ $purchase->id }}">
											<button class="btn btn-sm w-100 btn-success">View</button>
										</a>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
					{{ $purchases->links() }}
				@else
					User has no purchases
				@endif
			</div>
		</div>
	</div>
	<div class="col-sm-12 col-lg-2">
		<div class="card">
			<div class="card-header">
				<i class="fa fa-users fa-fw"></i> Options
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-12 col-sm-12">
						@if ($userShow->admin)
							{{ html()->form('POST', url('/admin/users/' . $userShow->id . '/admin'))->open() }}
								@method('DELETE')
								<button type="submit" class="btn w-100 btn-info">Remove Admin</button>
							</form>
						@else
							{{ html()->form('POST', url('/admin/users/' . $userShow->id . '/admin'))->open() }}
								<button type="submit" class="btn w-100 btn-success">Make Admin</button>
							</form>
						@endif
						<small>This will add or remove access to this admin panel. This means they can access everything! BE CAREFUL!</small>
					</div>
					@if ($userShow->email != null && $userShow->password != null)
						<div class="col-12 col-sm-12">
							{{ html()->form('POST', url('/login/forgot'))->open() }}
	                            @csrf
								<input type="hidden" name="email" value="{{ $userShow->email }}">
								<button type="submit" class="btn w-100 btn-success">Reset Password</button>
							</form>
							<small>This will reset the users password and sent a verification link to their email. If they are using a 3rd party Login this will do nothing.</small>
						</div>
					@endif
				</div>
				<br>
				<h4>Change Username</h4>
				<hr>
				{{ html()->form('POST', url('/admin/users/' . $userShow->id))->open() }}
					<div class="mb-3">
						{{ html()->text('username', $userShow->username)->class('form-control') }}
					</div>
					<button type="submit" class="btn btn-success w-100">Submit</button>
				</form>
				<br>
				<h4>Danger Zone</h4>
				<hr>
				<div class="row">
					<div class="col-12 col-sm-12">
						@if (!$userShow->banned)
							{{ html()->form('POST', url('/admin/users/' . $userShow->id . '/ban'))->open() }}
								<button type="submit" class="btn w-100 btn-danger">Ban</button>
							</form>
						@else
							{{ html()->form('POST', url('/admin/users/' . $userShow->id . '/unban'))->open() }}
								<button type="submit" class="btn w-100 btn-success">Un-Ban</button>
							</form>
						@endif
					</div>
				</div>
			</div>
		</div>			
	</div>
</div>
 
@endsection