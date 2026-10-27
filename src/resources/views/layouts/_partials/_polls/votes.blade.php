<div class="row">
		<div class="col-12 col-sm-9 col-md-9">
			<table width="100%" class="table table-striped table-hover">
				@foreach ($poll->options->reverse() as $option)
					<tr class="table-row odd gradeX">
						<td width="30%">{{ $option->name }}</td>
						<td>
							<div class="progress-bar" role="progressbar" aria-valuenow="{{ number_format($option->getPercentage(), 2) }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $option->getPercentage() }}%;">
								{{ $option->getTotalVotes() }}
							</div>
						</td>
						@if (!$poll->hasEnded() && Auth::user())
							<td width="10%">
								@if (Auth::user() && !$option->hasVoted())
									<a href="/polls/{{ $poll->slug }}/options/{{ $option->id }}/vote">
										<button type="button" class="btn btn-success btn-sm w-100">Vote</button>
									</a>
								@elseif (Auth::user() && $option->hasVoted())
									<a href="/polls/{{ $poll->slug }}/options/{{ $option->id }}/abstain">
										<button type="button" class="btn btn-danger btn-sm w-100">Remove</button>
									</a>
								@endif
							</td>
						@endif
					</tr>
				@endforeach
			</table>
		</div>
		<div class="col-12 col-sm-3 col-md-3">
			@if (Auth::user())
				{{ html()->label('You have voted for...', 'name') }}
				@foreach ($poll->options as $option)
					@if ($option->hasVoted())
						<p>
							<a href="/polls/{{ $poll->slug }}/options/{{ $option->id }}/abstain">
								<button type="button" class="btn-close" aria-label="Remove vote"></button>
							</a>
							{{ $option->name }}
						</p>
					@endif
				@endforeach
				@if (!$poll->hasEnded() && $poll->allow_options_user)
					{{ html()->form('POST', url('/polls/' . $poll->slug . '/options'))->acceptsFiles()->open() }}
						<div class="mb-3">
							{{ html()->label('Add Options', 'name') }}
							@include ('layouts._partials._polls.add-options')
						</div>
						<button type="submit" class="btn btn-outline-secondary w-100">Submit</button> 
					</form>
				@endif
			@else
				<div class="alert alert-info">
					<p>Please log in to Vote</p>
				</div>
			@endif
		</div>
	</div>