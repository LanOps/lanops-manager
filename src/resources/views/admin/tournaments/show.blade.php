@extends ('layouts.admin-default')

@section ('page_title', 'Tournaments - ' . $tournament->name)

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="pb-2 mb-3 border-bottom">
			Tournament - {{ $tournament->name }}
			@if ($tournament->isComplete())
				<small> - Ended</small>
			@endif
		</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item">
				<a href="/admin/tournaments/">Tournaments</a>
			</li>
			<li class="breadcrumb-item active">
				{{ $tournament->name }}
			</li>
		</ol> 
	</div>
</div>

<div class="row">
	<div class="col-12 col-sm-8">
		<div class="card">
			<div class="card-header">
				<i class="fa fa-users fa-fw"></i> Participants
			</div>
			<div class="card-body">
				{{-- @if (
					($tournament->hasEvent() && $tournament->event->getEventParticipant()) ||
					(!$tournament->hasEvent())
					)
					@if (Auth::user() && !$tournament->isUserSignedUp(Auth::user()) && $tournament->signupsOpen() && !$tournament->hasTeams())
						{{ html()->form('POST', url('/tournaments/' . $tournament->slug . '/register'))->open() }}
							<button type="submit" class="btn btn-success btn-sm w-100">Signup</button>
						</form>
					@elseif (Auth::user() && !$tournament->isUserSignedUp(Auth::user()) && $tournament->signupsOpen() && $tournament->hasTeams())
						{{ html()->form('POST', url('/tournaments/' . $tournament->slug . '/registerTeam'))->open() }}
							<div class="row">
								<div class="col-12 col-md-6 mb-3 @error('team_name') @enderror">
									{{ html()->label('Team Name', 'team_name') }}
									<input id="team_name" type="team_name" class="form-control" name="team_name" required>
								</div>
								<div class="col-12 col-md-6 mb-3 @error('team_password') @enderror">
									{{ html()->label('Team Password', 'team_password') }}
									<input id="team_password" type="team_password" class="form-control" name="team_password">
								</div>
							</div>
							<button type="submit" class="btn btn-success btn-sm w-100">Register Team</button>
						</form>
					@elseif (Auth::user() && $tournament->isUserSignedUp(Auth::user()) && $tournament->signupsOpen())
						{{ html()->form('POST', url('/tournaments/' . $tournament->slug . '/unregister'))->open() }}
							<button type="submit" class="btn btn-danger btn-sm w-100">Remove Signup</button>
						</form>
					@endif
				@endif --}}
				@if ($tournament->hasTeams())
					<table width="100%" class="table table-striped table-hover" id="dataTables-example">
						<thead>
							<tr>
								<th>Team Name</th>
								<th>Participants</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							@if ($tournament->teams)
								@foreach ($tournament->teams as $team)
									<tr class="table-row odd gradeX">
										<td width="30%">{{ $team->name }} - {{ $team->getParticipantCount() }}/{{ $tournament->team_size }}</td>
										<td>
											@include('layouts._partials._tournaments.participantsList', ['participants' => $team->participants])
										</td>
										<td>
											{{-- @if (
												Auth::user() && !$tournament->isUserSignedUp(Auth::user()) && 
												$tournament->signupsOpen() && 
												(($tournament->hasEvent() && $tournament->event->getEventParticipant()) || !$tournament->hasEvent()))
												{{ html()->form('POST', url('/tournaments/' . $tournament->slug . '/register'))->open() }}
													{{ html()->hidden('tournament_team_id', $team->id) }}
													<button type="submit" class="btn btn-success btn-sm w-100">Join Team</button>
												</form>
											@endif --}}
										</td>
									</tr>
								@endforeach
							@endif
						</tbody>
					</table>
				@elseif ($tournament->hasParticipants())
					@include('layouts._partials._tournaments.participantsList', ['participants' => $tournament->participants])
				@endif
				{{-- <table width="100%" class="table table-striped table-hover" id="dataTables-example">
					<thead>
						<tr>
							<th>Avatar</th>
							<th>User</th>
							<th>Remove</th>
						</tr>
					</thead>
					<tbody>
						@if ($tournament->participants)
							@foreach ($tournament->participants as $participant)
								<tr class="table-row odd gradeX">
									<td width="30%"><x-avatar :user="$participant->user" class="img-fluid rounded" /></td>
									<td>
										{{ $participant->user->username }}
										@if ($steamProfile = Helpers::getSteamProfile($participant->user->steamid))
                            				<br><span class="text-muted"><small>Steam: {{ $steamProfile["name"] }}</small></span>
                        				@endif
									</td>
									<td>

									</td>
								</tr>
							@endforeach
						@endif
					</tbody>
				</table> --}}
			</div>  
		</div>
	</div>
	<div class="col-12 col-sm-4">
		
		<div class="card">
			<div class="card-header">
				<i class="fa fa-pencil fa-fw"></i> Edit {{ $tournament->name }}
			</div>
			<div class="card-body">
				{{ html()->label('Tournament Signup Link:', 'name') }}
				<a href="{{ $_SERVER['REQUEST_SCHEME'] }}://{{ $_SERVER['HTTP_HOST'] }}/tournaments/{{ $tournament->slug }}">
					{{ $_SERVER['REQUEST_SCHEME'] }}://{{ $_SERVER['HTTP_HOST'] }}/tournaments/{{ $tournament->slug }}
				</a>
				{{ html()->form('POST', url('/admin/tournaments/' . $tournament->slug))->acceptsFiles()->open() }}
					<div class="mb-3">
						{{ html()->label('Name', 'name') }}
						{{ html()->text('name', $tournament->name)->class('form-control') }}
					</div>
					<div class="mb-3">
						{{ html()->label('Status', 'status') }}
						{{ html()->select('status', App\Models\Tournament::getStatusArray(), $tournament->status)->class('form-select') }}
					</div>
					<div class="mb-3">
						{{ html()->label('Team Size (0 is no Teams)', 'team_size') }}
						{{ html()->text('team_size', $tournament->team_size)->id('name')->class('form-control') }}
					</div>
					<div class="mb-3">
						{{ html()->label('Link to Event', 'event_id') }}
						{{ html()->select('event_id', Helpers::getEventNames('DESC', 0, true), $tournament->event_id)->class('form-select') }}
					</div>
					<div class="mb-3">
						<button type="submit" class="btn btn-success w-100">Submit</button> 
					</div>
				</form>
				<hr>
				{{ html()->form('POST', url('/admin/tournaments/' . $tournament->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
					@method('DELETE')
					<button type="submit" class="btn btn-danger w-100">Delete</button>
				</form>
			</div>
		</div>
	</div>
</div>

@endsection