@extends ('layouts.admin-default')

@section ('page_title', 'Users')

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="pb-2 mb-3 border-bottom">Users</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item active">
				Users
			</li>
		</ol> 
	</div>
</div>

<div class="row">
	<div class="col-lg-12">

		<div class="card">
			<div class="card-header">
				<i class="fa fa-users fa-fw"></i> Users
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-striped table-hover">
						<thead>
							<tr>
								<th>Avatar</th>
								<th>User</th>
								<th>Name</th>
								<th>Admin</th>
								<th>Edit</th>
							</tr>
						</thead>
						<tbody>
							@foreach ($users as $user)
								<tr class="">
									<td width="3%">
										<img class="img-fluid rounded" src="{{ $user->avatar }}">
									</td>
									<td>
										{{ $user->username }}
										@if ($steamProfile = Helpers::getSteamProfile($user->steamid))
										 	<span class="text-muted"><small>Steam: {{ $steamProfile["name"] }}</small></span>
                        				@endif
									</td>
									<td>{{ $user->firstname }} {{ $user->surname }}</td>
									<td>
										@if ($user->admin)
											Yes
										@else
											No
										@endif
									</td>
									<td>
										<a href="/admin/users/{{ $user->id }}">
											<button class="btn btn-primary btn-sm w-100">Edit</button>
										</a>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
					{{ $users->links() }}
				</div>
			</div>  
		</div>
		
	</div>
</div>
 
@endsection