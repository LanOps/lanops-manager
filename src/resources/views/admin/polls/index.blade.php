@extends ('layouts.admin-default')

@section ('page_title', 'Polls')

@section ('content')
<div class="row">
	<div class="col-lg-12">
		<h3 class="page-header">Polls</h3>
		<ol class="breadcrumb">
			<li class="active">
				Polls
			</li>
		</ol> 
	</div>
</div>

<div class="row">
	<div class="col-xs-12 col-sm-8">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-users fa-fw"></i> Polls
			</div>
			<div class="panel-body">
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
										<button type="button" class="btn btn-primary btn-sm btn-block">Edit</button>
									</a>
								</td>
								<td width="15%">
									{{ html()->form('POST', url('/admin/polls/' . $poll->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
										@method('DELETE')
										<button type="submit" class="btn btn-danger btn-sm btn-block">Delete</button>
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
	<div class="col-xs-12 col-sm-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-plus fa-fw"></i> Create Poll
			</div>
			<div class="panel-body">
				{{ html()->form('POST', url('/admin/polls/'))->acceptsFiles()->open() }}
					<div class="form-group">
						{{ html()->label('Name', 'name') }}
						{{ html()->text('name')->class('form-control') }}
					</div>
					<div class="form-group">
						{{ html()->label('Description', 'description') }}
						{{ html()->textarea('description', '')->class('form-control')->rows('3') }}
					</div>
					<div class="form-group">
						{{ html()->label('Link to Event', 'event_id') }}
						{{ html()->select('event_id', Helpers::getEventNames('DESC', 0, true), '')->class('form-control') }}
					</div>
					{{ html()->label('Options', 'options') }}
					@include ('layouts._partials._polls.add-options')
					<div class="row">
						<div class="col-lg-6 col-sm-12 form-group">
							<div class="checkbox">
								<label>
									{{ html()->checkbox('allow_options_user', true) }} Allow users to add their own options?
								</label>
							</div>
						</div> 
						<div class="col-lg-6 col-sm-12 form-group">
							<div class="checkbox">
								<label>
									{{ html()->checkbox('allow_options_multi', true) }} Allow multiple options?
								</label>
							</div>
						</div> 
					</div>
					<button type="submit" class="btn btn-success btn-block">Submit</button> 
				</form>
			</div>
		</div>
	</div>
</div>

@endsection