@extends ('layouts.admin-default')

@section ('page_title', 'Admin')

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="pb-2 mb-3 border-bottom">Dashboard</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item active">
				<i class="fa fa-gauge"></i> Dashboard
			</li>
		</ol>
	</div>
</div>

<div class="row">
	<div class="col-lg-3 col-md-6">
		<div class="card border-primary">
			<div class="card-header text-bg-primary">
				<div class="row">
					<div class="col-3">
						<i class="fa fa-comments fa-5x"></i>
					</div>
					<div class="col-9 text-end">
						<div class="huge">{{ $comments->count() }}</div>
						<div>New Comments!</div>
					</div>
				</div>
			</div>
			<a href="/admin/news">
				<div class="card-footer">
					<span class="float-start">View Details</span>
					<span class="float-end"><i class="fa fa-circle-arrow-right"></i></span>
					<div class="clearfix"></div>
				</div>
			</a>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6">
		<div class="card border-success">
				<div class="card-header text-bg-success">
					<div class="row">
						<div class="col-3">
							<i class="fa fa-list-check fa-5x"></i>
						</div>
						<div class="col-9 text-end">
							<div class="huge">{{ $votes->count() }}</div>
							<div>New Votes!</div>
						</div>
					</div>
			</div>
			<a href="/admin/polls">
				<div class="card-footer">
					<span class="float-start">View Details</span>
					<span class="float-end"><i class="fa fa-circle-arrow-right"></i></span>
					<div class="clearfix"></div>
				</div>
			</a>
		</div>
	</div>
	
	<div class="col-lg-3 col-md-6">
		<div class="card border-danger">
			<div class="card-header text-bg-danger">
				<div class="row">
					<div class="col-3">
						<i class="fa fa-life-ring fa-5x"></i>
					</div>
					<div class="col-9 text-end">
						<div class="huge">{{ $participants->count() }}</div>
						<div>New Participants!</div>
					</div>
				</div>
			</div>
			<a href="/admin/events">
				<div class="card-footer">
					<span class="float-start">View Details</span>
					<span class="float-end"><i class="fa fa-circle-arrow-right"></i></span>
					<div class="clearfix"></div>
				</div>
			</a>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-md-6 col-12" hidden>
		<div class="card">
			<div class="card-header">
				<i class="fa fa-ticket fa-fw"></i> Ticket Sales Per Month
			</div>
			<div class="card-body">
				<div class="admin-chart">
					<canvas
						data-chart="bar"
						data-chart-colour="single"
						data-chart-labels="{{ json_encode(array_map(fn ($month) => date('M Y', strtotime($month . '-01')), array_keys($ticketBreakdown))) }}"
						data-chart-values="{{ json_encode(array_map('count', array_values($ticketBreakdown))) }}"
						data-chart-series="Tickets"
						aria-label="Ticket sales per month"
						role="img"
					></canvas>
				</div>
			</div>
		</div>	
	</div>

	<div class="col-md-6 col-12" hidden>
		<div class="card">
			<div class="card-header">
				<i class="fa fa-credit-card fa-fw"></i> Orders Per Month
			</div>
			<div class="card-body">
				<div id="orders-breakdown"></div>
			</div>
		</div>	
	</div>

	<div class="col-md-6 col-12">
		<div class="card">
			<div class="card-header">
				<i class="fa fa-user fa-fw"></i> Users
			</div>
			<div class="card-body">
				<ul class="list-group">
					@if ($userLastLoggedIn)
						<li class="list-group-item list-group-item-info"><strong>Last Logged In: <span class="float-end">{{ $userLastLoggedIn->username }} on {{ $userLastLoggedIn->last_login }}</span></strong></li>
					@endif
					<li class="list-group-item list-group-item-info"><strong>No. of Users: <span class="float-end">{{ $userCount }}</span></strong></li>
				</ul>
			</div>
		</div>	
	</div>

	<div class="col-md-6 col-12">
		<div class="card">
			<div class="card-header">
				<i class="fa fa-book fa-fw"></i> Events
			</div>
			<div class="card-body">
				<ul class="list-group">
					<li class="list-group-item list-group-item-info"><strong>Next Event: <span class="float-end">{{ $nextEvent }}</span></strong></li>
					<li class="list-group-item list-group-item-info"><strong>No of Events: <span class="float-end">{{ $eventCount }}</span></strong></li>
					<li class="list-group-item list-group-item-info"><strong>No of Attendees: <span class="float-end">{{ $participantCount }}</span></strong></li>
				</ul>
			</div>
		</div>	
	</div>

	<div class="col-md-6 col-12">
		<div class="card">
			<div class="card-header">
				<i class="fa fa-list fa-fw"></i> Active Polls
			</div>
			<div class="card-body">
				<ul class="list-group">
					@if ($activePolls->count() > 0)
						@foreach ($activePolls as $poll)
							<li class="list-group-item list-group-item-info"><strong>{{ $poll->name }}: <span class="float-end">{{ $poll->getTotalVotes() }}</span></strong></li>
						@endforeach
					@else
						<li class="list-group-item list-group-item-info"><strong>Nothing to see here...</strong></li>
					@endif
				</ul>
			</div>
		</div>	
	</div>

	<div class="col-md-6 col-12">
		<div class="card">
			<div class="card-header">
				<i class="fa fa-wrench fa-fw"></i> Features
			</div>
			<div class="card-body">
				<ul class="list-group">

				</ul>
			</div>
		</div>	
	</div>
</div>


@endsection
