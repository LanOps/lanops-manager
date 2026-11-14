@extends ('layouts.admin-default')

@section ('page_title', $event->display_name)

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="pb-2 mb-3 border-bottom">{{ $event->display_name }}</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item">
				<a href="/admin/events/">Events</a>
			</li>
			<li class="breadcrumb-item active">
				{{ $event->display_name }}
			</li>
		</ol>
	</div>
</div>

@include ('layouts._partials._admin._event.dashMini')

<div class="row">
	<div class="col-lg-8">

		<div class="card">
			<div class="card-header">
				<i class="fa fa-wrench fa-fw"></i> Settings
			</div>
			<div class="card-body">
				@if ($errors->any())
					<div class="alert alert-danger">
						<ul>
							@foreach ($errors->all() as $error)
								<li>{{ $error }}</li>
							@endforeach
						</ul>
					</div>
				@endif
				{{ html()->form('POST', url('/admin/events/' . $event->slug))->acceptsFiles()->open() }}
					<div class="row">
						<div class="col-md-6 col-sm-12">
							<div class="mb-3">
								{{ html()->label('Name', 'event_name') }}
								{{ html()->text('event_name', $event->display_name)->class('form-control') }}
							</div>
							<div class="row">
								<div class="col-md-6 col-12">
									<div class="row">
										<div class="col-md-6 col-12">
											<div class="mb-3">
												{{ html()->label('Capacity', 'capacity') }}
												{{ html()->text('capacity', $event->capacity)->class('form-control') }}
											</div>
										</div>
										<div class="col-md-6 col-12">
											<div class="mb-3">
												{{ html()->label('Seating', 'seating_cap') }}
												{{ html()->text('seating_cap', $event->getSeatingCapacity())->class('form-control')->disabled() }}
											</div>
										</div>
									</div>
								</div>
								<div class="col-md-6 col-12">
									<div class="row">
										<div class="col-md-6 col-12">
											<div class="mb-3">
												{{ html()->label('Status', 'event_status') }}
												{{ 
													html()->select('status', array(
															'draft'=>'Draft',
															'preview'=>'Preview',
															'published'=>'Published',
															'private' => 'Private'
														), strtolower($event->status))->class('form-control')
												}}
											</div>
										</div>
										<div class="col-md-6 col-12">
											<div class="mb-3">
												{{ html()->label('Type', 'event_type') }}
												{{ html()->select('type', App\Models\Event::getTypeArray(), $event->type)->class('form-select') }}
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-6 col-sm-12">
							<div class="row">
								<div class="col-md-6 col-sm-12">
									<div class="mb-3">
										{{ html()->label('Start Date', 'start_date') }}
										{{ html()->date('start_date', date('Y-m-d', strtotime($event->start)))->class('form-control') }}
									</div>
									<div class="mb-3">
										{{ html()->label('End Date', 'end_date') }}
										{{ html()->date('end_date', date('Y-m-d', strtotime($event->end)))->class('form-control') }}
									</div>
								</div>
								<div class="col-md-6 col-sm-12">
									<div class="mb-3">
										{{ html()->label('Start Time', 'start_time') }}
										{{ html()->text('start_time', date('H:i', strtotime($event->start)))->class('form-control') }}
									</div>
									<div class="mb-3">
										{{ html()->label('End Time', 'end_time') }}
										{{ html()->text('end_time', date('H:i', strtotime($event->end)))->class('form-control') }}
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-8 col-sm-12">
							<strong>Venue</strong>
							<address>
								@if ($event->venue->display_name) {{ $event->venue->display_name }}<br> @endif
								@if ($event->venue->address_1) {{ $event->venue->address_1 }}<br> @endif
								@if ($event->venue->address_2) {{ $event->venue->address_2 }}<br> @endif
								@if ($event->venue->address_street) {{ $event->venue->address_street }}<br> @endif
								@if ($event->venue->address_city) {{ $event->venue->address_city }}<br> @endif
								@if ($event->venue->address_postcode) {{ $event->venue->address_postcode }}<br> @endif
								@if ($event->venue->address_country) {{ $event->venue->address_country }}<br> @endif
							</address>
						</div>
						<div class="col-md-4 col-sm-12">
							
						</div>
					</div>
					<div class="row">
						<div class="col-12">
							<div class="mb-3">
								{{ html()->label('Short Description', 'desc_short') }}
								{{ html()->textarea('desc_short', $event->desc_short)->class('form-control wysiwyg-editor')->rows('10') }}
							</div>
							<div class="mb-3">
								{{ html()->label('Long Description', 'desc_long') }}
								{{ html()->textarea('desc_long', $event->desc_long)->class('form-control wysiwyg-editor')->rows('4') }}
							</div>
						</div>
					</div>
					<button type="submit" class="btn btn-success w-100">Submit</button>
				</form>
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<i class="fa fa-info fa-fw"></i> Event Information
				<a href="#" class="btn btn-info btn-sm float-end" data-bs-toggle="modal" data-bs-target="#addEventInformationModal">Add Event Information</a>
			</div>
			<div class="card-body">
				@if ($event->information->count() != 0)
					@foreach ($event->information as $section)
						<div class="card">
							<div class="card-header">
								<h4 class="card-title">
									<a data-bs-toggle="collapse" data-bs-parent="#accordion" href="#collaspe-{{ $section->id }}" aria-expanded="false" class="collapsed">{{ $section->title }}</a>
									<div class="float-end">
										{{ html()->form('POST', url('/admin/information/' . $section->id))->open() }}
											@method('DELETE')
											{{ html()->input('submit')->value('Delete')->class('btn btn-danger btn-sm float-end')->attribute('style', 'margin-top:-2px;') }}
										</form>
									</div>
								</h4>
							</div>
							<div id="collaspe-{{ $section->id }}" class="collapse" aria-expanded="false">
								<div class="card-body">
									{{ html()->form('POST', url('/admin/information/' . $section->id))->acceptsFiles()->open() }}
										<div class="row">
											<div class="col-md-6 col-sm-12">
												<div class="mb-3">
													{{ html()->label('Title', 'title') }}
													{{ html()->text('title', $section->title)->class('form-control') }}
												</div>
											</div>
											<div class="col-md-6 col-sm-12">
												<div class="mb-3">
													{{ html()->label('Image', 'image') }}
													{{ html()->file('image')->class('form-control') }}
												</div>
											</div>
											<div class="mb-3 col-lg-6 col-sm-12">
												{{ html()->label('Order', 'order') }}
												{{ html()->number('order', $section->order)->class('form-control') }}
											</div>
										</div>
										<div class="mb-3">
											{{ html()->label('Text', 'text') }}
											{{ html()->textarea('text', $section->text)->class('form-control wysiwyg-editor')->rows('4') }}
										</div>
										@if($section->hasMedia())
											<div class="row">
												<div class="mb-3 col-lg-6">
													{{ html()->label('Image Preview', 'preview') }}
													<center>
														<img class="img-fluid img-thumbnail" src="{{ $section->getFirstMediaUrl() }}" />
													</center>
												</div>  
											</div>
										@endif
										<button type="submit" class="btn btn-success w-100">Update</button>
									</form>
								</div>
							</div>
						</div>
					@endforeach
				@else
					<h4>None</h4>
				@endif
			</div>
		</div>

	</div>
	<div class="col-lg-4">

		@if ($event->status == 'PRIVATE')
			<div class="card">
				<div class="card-header">
					<i class="fa fa-lock fa-fw"></i> Private Link
				</div>
				<div class="card-body">
					<p class="small text-body-secondary">
						This event is hidden from the website. Share this link with the people you want to invite:
						anyone with it can see the event and buy tickets. People with a ticket can always see it.
					</p>
					@if ($event->getPrivateUrl())
						<div class="input-group mb-3">
							<input type="text" class="form-control" id="private-event-link" value="{{ $event->getPrivateUrl() }}" readonly aria-label="Private link">
							<button type="button" class="btn btn-primary" data-copy-target="#private-event-link">
								<i class="fa fa-copy fa-fw"></i> Copy
							</button>
						</div>
					@endif
					{{ html()->form('POST', url('/admin/events/' . $event->slug . '/private-link'))->open() }}
						<button type="submit" class="btn btn-outline-danger btn-sm w-100"
							@if ($event->getPrivateUrl()) onclick="return confirm('Make a new link? The old link stops working, for anyone who has not bought a ticket yet.');" @endif>
							<i class="fa fa-refresh fa-fw"></i> {{ $event->getPrivateUrl() ? 'Make a new link' : 'Make a link' }}
						</button>
					</form>
				</div>
			</div>
		@endif

		@if (config('app.discord_bot_url') != '')
			<div class="card">
				<div class="card-header">
					<i class="fa fa-bullhorn fa-fw"></i> Discord Status
					@if (!$event->discord_link_enabled)
						<a href="#" class="btn btn-info btn-sm float-end" data-bs-toggle="modal" data-bs-target="#linkDiscordModal">Link Bot</a>
					@else
						<a href="#" class="btn btn-danger btn-sm float-end" data-bs-toggle="modal" data-bs-target="#unlinkDiscordModal">Unlink Bot</a>
					@endif
				</div>
				<div class="card-body">
					@if ($event->discord_link_enabled)
						<p>Channel ID: {{ $event->discord_channel_id }}</p>
						<p>Role ID: {{ $event->discord_role_id }}</p>
						<p>Event ID: {{ $event->discord_event_id }}</p>
					@else
						<h4>Please link the bot</h4>
					@endif
				</div>
			</div>
		@endif

		<div class="card">
			<div class="card-header">
				<i class="fa fa-ticket fa-fw"></i> Tickets
				<a href="/admin/events/{{ $event->slug }}/tickets" style="margin-left:3px;" class="btn btn-info btn-sm float-end">All Tickets</a>
				<a href="#" class="btn btn-info btn-sm float-end" data-bs-toggle="modal" data-bs-target="#addTicketModal">Add Ticket</a>
			</div>
			<div class="card-body">
				@if ($event->tickets->count() != 0)
					<div class="list-group">
						@foreach ($event->tickets as $ticket)
							<a href="/admin/events/{{ $event->slug }}/tickets/{{ $ticket->id }}" class="list-group-item">
								<i class="fa fa-pencil fa-fw"></i> {{ $ticket->name }} - {{ config('app.currency_symbol') }}{{ $ticket->price }}
								<span class="float-end text-muted small">
									@if($ticket->quantity != 0)
										<em>{{ $ticket->participants()->count() }} / {{ $ticket->quantity }}</em>
									@else
										<em>Unlimited</em>
									@endif
								</span>
							</a>
						@endforeach
					</div>
				@else
					<h4>None</h4>
				@endif
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<i class="fa fa-list fa-fw"></i> Polls
			</div>
			<div class="card-body">
				@if (!$event->polls->isEmpty())
					<div class="list-group">
						@foreach ($event->polls as $poll)
							<a href="/admin/polls/{{ $poll->slug }}" class="list-group-item">
								<i class="fa fa-pencil fa-fw"></i> {{ $poll->name }}
							</a>
						@endforeach
					</div>
				@else
					<h4>None</h4>
				@endif
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<i class="fa fa-user fa-fw"></i> Attendees
				<a href="/admin/events/{{ $event->slug }}/participants" style="margin-left:3px;" class="btn btn-info btn-sm float-end">All Attendees</a>
				<a href="/admin/events/{{ $event->slug }}/tickets#freebies" class="btn btn-info btn-sm float-end">Freebies</a>
			</div>
			<div class="card-body">
				@if (!$event->eventParticipants->isEmpty())
					<div class="list-group">
						{{-- One Steam request for the whole list, instead of one per row --}}
						@php
						    Helpers::getSteamProfiles($participants->pluck('user.steamid')->all());
						@endphp
						@foreach ($participants as $participant)
							<a href="/admin/events/{{ $event->slug }}/participants/{{ $participant->id }}" class="list-group-item">
								<i class="fa fa-comment fa-fw"></i> {{ $participant->user->username }}
								@if ($steamProfile = Helpers::getSteamProfile($participant->user->steamid))
									<br><span class="text-muted"><small>Steam: {{ $steamProfile["name"] }}</small></span>
								@endif
								<span class="float-end text-muted small">
									<em>{{ date('d-m-y H:i', strtotime($participant->created_at)) }}</em>
								</span>
							</a>
						@endforeach
						{{ $participants->links() }}
					</div>
				@else
					<h4>None</h4>
				@endif
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<i class="fa fa-wrench fa-fw"></i> Danger Zone
			</div>
			<div class="card-body">
				{{ html()->form('POST', url('/admin/events/' . $event->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
					@method('DELETE')
					<button type="submit" class="btn btn-danger w-100">Delete</button>
				</form>
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<i class="fa fa-clone fa-fw"></i> Duplicate Event
			</div>
			<div class="card-body">
				@php
					// "LanOps 60" suggests "LanOps 61"
					$suggestedName = preg_match('/^(.*?)(\d+)\s*$/', $event->display_name, $nameMatch)
						? $nameMatch[1] . ((int) $nameMatch[2] + 1)
						: $event->display_name . ' Copy';
					$duplicateStart = \Carbon\Carbon::parse($event->start);
				@endphp
				<p class="small text-muted">
					Makes a draft copy with the tickets, seating plans and information. The end date, ticket sale dates and timetable move by the same amount as the start.
				</p>
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/duplicate'))->open() }}
					@if ($errors->hasAny(['dup_name', 'dup_start_date', 'dup_start_time']))
						<div class="alert alert-danger">
							<ul class="mb-0">
								@foreach (collect(['dup_name', 'dup_start_date', 'dup_start_time'])->flatMap(fn ($key) => $errors->get($key)) as $error)
									<li>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
					@endif
					<div class="mb-3">
						<label for="duplicate_event_name">New event name</label>
						<input type="text" id="duplicate_event_name" name="dup_name" class="form-control" required maxlength="255"
							value="{{ old('dup_name') !== null ? old('dup_name') : $suggestedName }}">
					</div>
					<div class="row">
						<div class="mb-3 col-7">
							<label for="duplicate_start_date">Starts</label>
							<input type="date" id="duplicate_start_date" name="dup_start_date" class="form-control" required
								value="{{ old('dup_name') !== null ? old('dup_start_date') : $duplicateStart->format('Y-m-d') }}">
						</div>
						<div class="mb-3 col-5">
							<label for="duplicate_start_time">At</label>
							<input type="time" id="duplicate_start_time" name="dup_start_time" class="form-control" required
								value="{{ old('dup_name') !== null ? old('dup_start_time') : $duplicateStart->format('H:i') }}">
						</div>
					</div>
					<div class="form-check mb-3">
						<input type="hidden" name="copy_timetables" value="0">
						<input type="checkbox" class="form-check-input" id="duplicate_copy_timetables" name="copy_timetables" value="1"
							@checked(old('dup_name') !== null ? old('copy_timetables') : true)>
						<label class="form-check-label" for="duplicate_copy_timetables">Copy the timetables too</label>
					</div>
					<button type="submit" class="btn btn-primary w-100"><i class="fa fa-clone fa-fw"></i> Create copy</button>
				</form>
			</div>
		</div>
		</div>

	</div>
</div>

<!-- Modals -->
	
<div class="modal fade" id="addTicketModal" tabindex="-1" role="dialog" aria-labelledby="addTicketModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="addTicketModalLabel">Add Ticket</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/tickets'))->open() }}
					@include('layouts._partials._admin._event._tickets.form', ['empty' => true])
				</form>
			</div>
		</div>
	</div>
</div>

@if (config('app.discord_bot_url') != '')
	<div class="modal fade" id="linkDiscordModal" tabindex="-1" role="dialog" aria-labelledby="linkDiscordModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="linkDiscordModalLabel">Link Discord</h4>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					@if ($errors->any())
						<div class="alert alert-danger">
							<ul>
								@foreach ($errors->all() as $error)
									<li>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
					@endif
					{{ html()->form('POST', url('/admin/events/' . $event->slug . '/discord/link'))->acceptsFiles()->open() }}
						Are you Sure you want to Link this Event to Discord? It will do the following:
						<ul>
							<li>Create New Scheduled Event</li>
							<li>Create New Event Channel</li>
							<li>Create New Event Role</li>
							<li>Announce New Attendees</li>
						</ul>
						<button type="submit" class="btn btn-outline-secondary w-100">Submit</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="unlinkDiscordModal" tabindex="-1" role="dialog" aria-labelledby="unlinkDiscordModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="unlinkDiscordModalLabel">Unlink Discord</h4>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					@if ($errors->any())
						<div class="alert alert-danger">
							<ul>
								@foreach ($errors->all() as $error)
									<li>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
					@endif
					{{ html()->form('POST', url('/admin/events/' . $event->slug . '/discord/unlink'))->acceptsFiles()->open() }}
						Are you Sure you want to Unlink this Event from Discord? It will do the following:
						<ul>
							<li>Stop sending new attendees, gifts and timetable updates to the Bot</li>
						</ul>
						The event's Discord channel and role are left as they are. You can link the event again later, which creates new ones.
						<button type="submit" class="btn btn-outline-secondary w-100">Submit</button>
					</form>
				</div>
			</div>
		</div>
	</div>

@endif

<div class="modal fade" id="addAnnouncementModal" tabindex="-1" role="dialog" aria-labelledby="addAnnouncementModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="announcementModalLabel">Add Announcement</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				@if ($errors->any())
				  	<div class="alert alert-danger">
				        <ul>
				          	@foreach ($errors->all() as $error)
				            	<li>{{ $error }}</li>
				          	@endforeach
				        </ul>
				  	</div>
				@endif
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="addEventInformationModal" tabindex="-1" role="dialog" aria-labelledby="addEventInformationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="addEventInformationModal">Add Event Information</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/information'))->acceptsFiles()->open() }}
					<div class="row">
						<div class="col-md-6 col-sm-12">
							<div class="mb-3">
								{{ html()->label('Title', 'title') }}
								{{ html()->text('title')->class('form-control') }}
							</div>
						</div>
						<div class="col-md-6 col-sm-12">
							<div class="mb-3">
								{{ html()->label('Image', 'image') }}
								{{ html()->file('image')->class('form-control') }}
							</div>
						</div>
					</div>
					<div class="mb-3">
						{{ html()->label('Text', 'text') }}
						{{ html()->textarea('text')->class('form-control')->rows('4') }}
					</div>
					<button type="submit" class="btn btn-outline-secondary">Submit</button>
				</form>
			</div>
		</div>
	</div>
</div>

@endsection
