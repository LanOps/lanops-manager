@extends ('layouts.admin-default')

@section ('page_title', 'Polls')

@section ('content')
<div class="row">
	<div class="col-lg-12">
		<h3 class="pb-2 mb-3 border-bottom">Polls</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item active">
				Polls
			</li>
		</ol> 
	</div>
</div>

<div class="row">
	<div class="col-12 col-sm-8">
		<div class="card">
			<div class="card-header">
				<i class="fa fa-users fa-fw"></i> Polls
			</div>
			<div class="card-body">
				<table width="100%" class="table table-striped table-hover">
					<thead>
						<tr>
							<th>Name</th>
							<th>Status</th>
							<th>Votes</th>
							<th>Options</th>
							<th>Created By</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($polls as $poll)
							<tr class="table-row odd gradeX" data-href="/admin/polls/{{ $poll->slug }}">
								<td>{{ $poll->name }}</td>
								<td>
									@if (!$poll->hasEnded())
										{{ $poll->status }}
										@if ($poll->getScheduledEnd())
											<br><small>Ends {{ date('H:i d-m-Y', strtotime($poll->getScheduledEnd())) }}</small>
										@endif
									@else
										ENDED
									@endif
								</td>
								<td>{{ $poll->getTotalVotes() }}</td>
								<td>
									{{ $poll->options->count() }}
								</td>
								<td><small>{{ $poll->user->username }}</small></td>
								<td width="15%">
									<a href="/admin/polls/{{ $poll->slug }}">
										<button type="button" class="btn btn-primary btn-sm w-100">Edit</button>
									</a>
								</td>
								<td width="15%">
									{{ html()->form('POST', url('/admin/polls/' . $poll->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
										@method('DELETE')
										<button type="submit" class="btn btn-danger btn-sm w-100">Delete</button>
									</form>
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
				{{ $polls->links() }}
			</div>  
		</div>
	</div>
	<div class="col-12 col-sm-4">
		<div class="card">
			<div class="card-header">
				<i class="fa fa-plus fa-fw"></i> Create Poll
			</div>
			<div class="card-body">
				{{ html()->form('POST', url('/admin/polls/'))->acceptsFiles()->open() }}
					<div class="mb-3">
						{{ html()->label('Name', 'name') }}
						{{ html()->text('name')->class('form-control') }}
					</div>
					<div class="mb-3">
						{{ html()->label('Description', 'description') }}
						{{ html()->textarea('description', '')->class('form-control')->rows('3') }}
					</div>
					<div class="mb-3">
						{{ html()->label('Link to Event', 'event_id') }}
						{{ html()->select('event_id', Helpers::getEventNames('DESC', 0, true), '')->class('form-select') }}
					</div>
					<div class="mb-3">
						{{ html()->label('Voting Ends (optional)', 'end') }}
						{{ html()->input('datetime-local', 'end', '')->class('form-control') }}
					</div>
					{{ html()->label('Options', 'options') }}
					@include ('layouts._partials._polls.add-options')
					<div class="row">
						<div class="col-lg-6 col-sm-12 mb-3">
							<div class="form-check">
								<label class="form-check-label">
									{{ html()->checkbox('allow_options_user', true)->class('form-check-input') }} Allow users to add their own options?
								</label>
							</div>
						</div> 
						<div class="col-lg-6 col-sm-12 mb-3">
							<div class="form-check">
								<label class="form-check-label">
									{{ html()->checkbox('allow_options_multi', true)->class('form-check-input') }} Allow multiple options?
								</label>
							</div>
						</div> 
					</div>
					<button type="submit" class="btn btn-success w-100">Submit</button> 
				</form>
			</div>
		</div>
	</div>
</div>

@endsection