@extends ('layouts.admin-default')

@section ('page_title', 'Tournaments')

@section ('content')
<div class="row">
	<div class="col-lg-12">
		<h3 class="pb-2 mb-3 border-bottom">Tournaments</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item active">
				Tournaments
			</li>
		</ol> 
	</div>
</div>

<div class="row">
	<div class="col-12 col-sm-8">
		<div class="card">
			<div class="card-header">
				<i class="fa fa-users fa-fw"></i> Tournaments
			</div>
			<div class="card-body">
				<table width="100%" class="table table-striped table-hover">
					<thead>
						<tr>
							<th>Name</th>
							<th>Event</th>
							<th>Teams Size</th>
							<th>Participants</th>
							<th>Status</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($tournaments as $tournament)
							<tr class="table-row odd gradeX" data-href="/admin/tournaments/{{ $tournament->slug }}">
								<td>{{ $tournament->name }}</td>
								<td>
									@if ($tournament->hasEvent())
										{{ $tournament->event->display_name }}
									@else
										None
									@endif
								</td>
								<td>
									@if ($tournament->hasTeams())
										{{ $tournament->team_size }}
									@else
										None
									@endif
								</td>
								<td>{{ $tournament->participants()->count() }}</td>
								<td>{{ $tournament->status }}</td>
								<td width="15%">
									<a href="/admin/tournaments/{{ $tournament->slug }}">
										<button type="button" class="btn btn-primary btn-sm w-100">Edit</button>
									</a>
								</td>
								<td width="15%">
									{{ html()->form('POST', url('/admin/tournaments/' . $tournament->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
										@method('DELETE')
										<button type="submit" class="btn btn-danger btn-sm w-100">Delete</button>
									</form>
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
				{{ $tournaments->links() }}
			</div>  
		</div>
	</div>
	<div class="col-12 col-sm-4">
		<div class="card">
			<div class="card-header">
				<i class="fa fa-plus fa-fw"></i> Create Tournament
			</div>
			<div class="card-body">
				{{ html()->form('POST', url('/admin/tournaments/'))->acceptsFiles()->open() }}
					<div class="mb-3">
						{{ html()->label('Name', 'name') }}
						{{ html()->text('name')->class('form-control') }}
					</div>
					<div class="mb-3">
						{{ html()->label('Team Size (0 is no Teams)', 'team_size') }}
						{{ html()->text('team_size', 0)->id('name')->class('form-control') }}
					</div>
					<div class="mb-3">
						{{ html()->label('Link to Event', 'event_id') }}
						{{ html()->select('event_id', Helpers::getEventNames('DESC', 0, true), '')->class('form-select') }}
					</div>
					<button type="submit" class="btn btn-success w-100">Submit</button> 
				</form>
			</div>
		</div>
	</div>
</div>

@endsection