@extends ('layouts.admin-default')

@section ('page_title', 'Purchases')

@section ('content')
<div class="row">
	<div class="col-lg-12">
		<h3 class="pb-2 mb-3 border-bottom">Purchases</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item active">
				Purchases
			</li>
		</ol> 
	</div>
</div>

<div class="row">
	<div class="col-12 col-sm-12">
		<div class="card">
			<div class="card-header">
				<i class="fa fa-credit-card fa-fw"></i> Purchases
			</div>
			<div class="card-body">
				<table width="100%" class="table table-striped table-hover">
					<thead>
						<tr>
							<th>ID</th>
							<th>User</th>
							<th>Name</th>
							<th>Status</th>
							<th>Type</th>
							<th>Paypal Email</th>
							<th>Transaction ID</th>
							<th>Referral Code Used</th>
							<th>Referral Redeemed</th>
							<th>Created At</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						{{-- One Steam request for the whole list, instead of one per row --}}
						@php
						    Helpers::getSteamProfiles($purchases->pluck('user.steamid')->all());
						@endphp
						@foreach ($purchases as $purchase)
							@php
								$statusColor = 'warning';
								if ($purchase->status == 'Success') {
									$statusColor = 'success';
								}elseif($purchase->status == 'Danger') {
									$statusColor = 'danger';
								}
							@endphp
							<tr class="{{ $statusColor }}">
								<td>{{ $purchase->id }}</td>
								<td>
									{{ $purchase->user->username }}
									@if ($steamProfile = Helpers::getSteamProfile($purchase->user->steamid))
										<br><span class="text-muted"><small>Steam: {{ $steamProfile["name"] }}</small></span>
									@endif
								</td>
								<td>{{ $purchase->user->firstname }} {{ $purchase->user->surname }}</td>
								<td>
									{{ $purchase->status }}
									@if ($purchase->getRefundStatus())
										<br><span class="badge text-bg-warning">{{ $purchase->getRefundStatus() }}</span>
									@endif
								</td>
								<td>{{ $purchase->type }}</td>
								<td>{{ $purchase->paypal_email }}</td>
								<td>{{ $purchase->transaction_id }}</td>
								<td>									
									@if ($purchase->referral_code_user_id )
										Yes
									@else
										No
									@endif
								</td>
								<td>
									@if ($purchase->referral_discount_total > 0)
										Yes
									@else
										No
									@endif
								</td>
								<td>{{ $purchase->created_at }}</td>
								<td>
									<a href="/admin/purchases/{{ $purchase->id }}">
										<button class="btn w-100 btn-success btn-sm">
											View
										</button>
									</a>
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
				{{ $purchases->links() }}
			</div>  
		</div>
	</div>
</div>

@endsection