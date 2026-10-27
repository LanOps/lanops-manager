@extends ('layouts.admin-default')

@section ('page_title', 'Polls')

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="page-header">
			Polls - {{ $poll->name }}
			@if ($poll->hasEnded())
				<small> - Ended</small>
			@endif
		</h3>
		<ol class="breadcrumb">
			<li>
				<a href="/admin/polls/">Polls</a>
			</li>
			<li class="active">
				{{ $poll->name }}
			</li>
		</ol> 
	</div>
</div>

<div class="row">
	<div class="col-xs-12 col-sm-8">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-users fa-fw"></i> Options
			</div>
			<div class="panel-body">
				<table width="100%" class="table table-striped table-hover" id="dataTables-example">
					<thead>
						<tr>
							<th>Name</th>
							<th>Votes</th>
							<th>Percentage %</th>
							<th>Added By</th>
						</tr>
					</thead>
					<tbody>
						@if (!$poll->options->isEmpty())
							@foreach ($poll->options->reverse() as $option)
								<tr class="table-row odd gradeX">
									<td width="30%">{{ $option->name }}</td>
									<td width="5%">{{ $option->getTotalVotes() }}</td>
									<td width="40%">
										<div class="progress-bar" role="progressbar" aria-valuenow="{{ $option->getPercentage() }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $option->getPercentage() }}%;">
											{{ $option->getPercentage() }}%
										</div>
									</td>
									<td width="25%"><small>{{ $option->user->username }}</small></td>
									<td>
										@if ($option->getTotalVotes() <= 0)
											{{ html()->form('POST', url('/admin/polls/' . $poll->slug . '/options/' . $option->id))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
												@method('DELETE')
												<button type="submit" class="btn btn-danger btn-sm btn-block">Delete</button>
											</form>
										@endif
									</td>
								</tr>
							@endforeach
						@endif
					</tbody>
				</table>
			</div>  
		</div>
	</div>
	<div class="col-xs-12 col-sm-4">
		
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-pencil fa-fw"></i> Edit {{ $poll->name }}
			</div>
			<div class="panel-body">
					{{ html()->label('Poll Link:', 'name') }}
				<a href="{{ $_SERVER['REQUEST_SCHEME'] }}://{{ $_SERVER['HTTP_HOST'] }}/polls/{{ $poll->slug }}">
					{{ $_SERVER['REQUEST_SCHEME'] }}://{{ $_SERVER['HTTP_HOST'] }}/polls/{{ $poll->slug }}
				</a>
				{{ html()->form('POST', url('/admin/polls/' . $poll->slug))->acceptsFiles()->open() }}
					<div class="form-group">
						{{ html()->label('Name', 'name') }}
						{{ html()->text('name', $poll->name)->class('form-control') }}
					</div>
					<div class="form-group">
						{{ html()->label('Description', 'description') }}
						{{ html()->textarea('description', $poll->description)->class('form-control')->rows('3') }}
					</div>
					<div class="form-group">
						{{ html()->label('Status', 'status') }}
						{{ 
							html()->select('status', array(
									'draft'=>'Draft',
									'preview'=>'Preview',
									'published'=>'Published'
								), strtolower($poll->status))->class('form-control')
						}}
					</div>
					<div class="form-group">
						{{ html()->label('Link to Event', 'event_id') }}
						{{ html()->select('event_id', Helpers::getEventNames('DESC', 0, true), $poll->event_id)->class('form-control') }}
					</div>
					<div class="form-group">
						{{ html()->label('Allow User to Add Options', 'allow_options_users') }} @if ($poll->allow_options_user) True @else False @endif
						<br>
						{{ html()->label('Allow User to Select Multiple Options', 'allow_options_multi') }} @if ($poll->allow_options_multi) True @else False @endif
					</div>
					<div class="form-group">
						<button type="submit" class="btn btn-success btn-block">Submit</button> 
					</div>
				</form>
				@if (!$poll->hasEnded())
					<div class="form-group">
						{{ html()->form('POST', url('/admin/polls/' . $poll->slug . '/end'))->open() }}
							<button type="submit" class="btn btn-primary btn-block">End Poll</button>
						</form>
					</div>
				@endif
				<hr>
				{{ html()->form('POST', url('/admin/polls/' . $poll->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
					@method('DELETE')
					<button type="submit" class="btn btn-danger btn-block">Delete</button>
				</form>
			</div>
		</div>
		@if (!$poll->hasEnded())
			<div class="panel panel-default">
				<div class="panel-heading">
					<i class="fa fa-plus fa-fw"></i> Add Options
				</div>
				<div class="panel-body">
					{{ html()->form('POST', url('/admin/polls/' . $poll->slug . '/options'))->acceptsFiles()->open() }}
						<div class="form-group">
							@include ('layouts._partials._polls.add-options')
						</div>
						<button type="submit" class="btn btn-default btn-block">Submit</button> 
					</form>
				</div>
			</div>
		@endif
	</div>
</div>
 
@endsection