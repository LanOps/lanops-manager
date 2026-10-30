<div class="row">
		<div class="col-12 col-sm-9 col-md-9">
			<table width="100%" class="table table-striped table-hover">
				@foreach ($poll->options->reverse() as $option)
					<tr class="table-row odd gradeX">
						<td width="30%">{{ $option->name }}</td>
						<td>
							<div class="progress" style="height: 20px;">
								<div class="progress-bar" role="progressbar" aria-valuenow="{{ number_format($option->getPercentage(), 2) }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $option->getPercentage() }}%;">
									{{ $option->getTotalVotes() }}
								</div>
							</div>
						</td>
						@if (!$poll->hasEnded() && Auth::user())
							<td width="10%">
								@if (Auth::user() && !$option->hasVoted())
									<x-action-button action="/polls/{{ $poll->slug }}/options/{{ $option->id }}/vote" class="btn btn-success btn-sm w-100">Vote</x-action-button>
								@elseif (Auth::user() && $option->hasVoted())
									<x-action-button action="/polls/{{ $poll->slug }}/options/{{ $option->id }}/abstain" class="btn btn-danger btn-sm w-100">Remove</x-action-button>
								@endif
							</td>
						@endif
					</tr>
				@endforeach
			</table>
		</div>
		<div class="col-12 col-sm-3 col-md-3">
			@if ($poll->hasEnded())
				<div class="alert alert-secondary">
					Voting ended {{ date('H:i d-m-Y', strtotime($poll->end)) }}
				</div>
			@elseif ($poll->getScheduledEnd())
				<p class="small text-body-secondary">Voting closes {{ date('H:i d-m-Y', strtotime($poll->getScheduledEnd())) }}</p>
			@endif
			@if (Auth::user())
				@php
					$votedOptions = $poll->options->filter(fn ($option) => $option->hasVoted());
				@endphp
				<p class="fw-bold mb-1">You have voted for...</p>
				@forelse ($votedOptions as $option)
					<p class="mb-1">
						@if (!$poll->hasEnded())
							<x-action-button action="/polls/{{ $poll->slug }}/options/{{ $option->id }}/abstain" class="btn-close" aria-label="Remove vote"></x-action-button>
						@endif
						{{ $option->name }}
					</p>
				@empty
					<p class="text-body-secondary">
						@if ($poll->hasEnded())
							You didn't vote in this poll.
						@else
							Nothing yet.
						@endif
					</p>
				@endforelse
				@if (!$poll->hasEnded() && $poll->allow_options_user)
					{{ html()->form('POST', url('/polls/' . $poll->slug . '/options'))->acceptsFiles()->open() }}
						<div class="mb-3">
							{{ html()->label('Add Options', 'name') }}
							@include ('layouts._partials._polls.add-options')
						</div>
						<button type="submit" class="btn btn-outline-secondary w-100">Submit</button> 
					</form>
				@endif
			@elseif (!$poll->hasEnded())
				<div class="alert alert-info">
					<p>Please log in to Vote</p>
				</div>
			@endif
		</div>
	</div>