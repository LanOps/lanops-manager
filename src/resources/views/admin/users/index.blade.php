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
				<form method="GET" action="/admin/users" class="mb-3" role="search">
					<div class="input-group">
						<span class="input-group-text"><i class="fa fa-search fa-fw"></i></span>
						<input type="search" name="search" class="form-control" value="{{ $search }}"
							placeholder="Search username, name, email, Discord or Steam ID" aria-label="Search users">
						<button type="submit" class="btn btn-primary">Search</button>
						@if ($search !== '')
							<a href="/admin/users" class="btn btn-outline-secondary">Clear</a>
						@endif
					</div>
				</form>
				@if ($search !== '')
					<p class="text-muted small">
						{{ $users->total() }} {{ Str::plural('user', $users->total()) }} matching "{{ $search }}"
					</p>
				@endif
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
							{{-- One Steam request for the whole list, instead of one per row --}}
							@php
							    Helpers::getSteamProfiles($users->pluck('steamid')->all());
							@endphp
							@foreach ($users as $user)
								<tr class="">
									<td width="3%">
										<x-avatar :member="$user" class="img-fluid rounded" />
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
							@if ($users->isEmpty())
								<tr>
									<td colspan="5" class="text-center text-muted py-4">No users found.</td>
								</tr>
							@endif
						</tbody>
					</table>
					{{ $users->links() }}
				</div>
			</div>  
		</div>
		
	</div>
</div>
 
@endsection