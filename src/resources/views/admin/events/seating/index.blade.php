@extends ('layouts.admin-default')

@section ('page_title', 'Seating Plans - ' . $event->display_name)

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="pb-2 mb-3 border-bottom">Seating Plans</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item">
				<a href="/admin/events/">Events</a>
			</li>
			<li class="breadcrumb-item">
				<a href="/admin/events/{{ $event->slug }}">{{ $event->display_name }}</a> 
			</li>
			<li class="breadcrumb-item active">
				Seating Plans
			</li>
		</ol>
	</div>
</div>

@include ('layouts._partials._admin._event.dashMini')

<div class="row">
	<div class="col-lg-8">
		
		<div class="card">
			<div class="card-header">
				<i class="fa fa-calendar fa-fw"></i> Seating Plans
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-striped table-hover">
						<thead>
							<tr>
								<th>Name</th>
								<th>Status</th>
								<th></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							@foreach ($seatingPlans as $seatingPlan)
								<tr>
									<td>
										{{ $seatingPlan->name }}
										@if ($seatingPlan->name_short)
											| {{ $seatingPlan->name_short }}
										@endif
									</td>
									<td>
										{{ $seatingPlan->status }}
									</td>
									<td width="15%">
										<a href="/admin/events/{{ $event->slug }}/seating/{{ $seatingPlan->slug }}">
											<button type="button" class="btn btn-primary btn-sm w-100">Edit</button>
										</a>
									</td>
									<td width="15%">
										{{ html()->form('POST', url('/admin/events/' . $event->slug . '/seating/' . $seatingPlan->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
											@method('DELETE')
											<button type="submit" class="btn btn-danger btn-sm w-100">Delete</button>
										</form>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
					{{ $seatingPlans->links() }}
				</div>        
			</div>
		</div>
	
	</div>
	<div class="col-lg-4">

		<div class="card">
			<div class="card-header">
				<i class="fa fa-plus fa-fw"></i> Add New Seating Plan
			</div>
			<div class="card-body">
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/seating'))->acceptsFiles()->open() }}
					@if ($errors->any())
						<div class="alert alert-danger">
							<ul>
								@foreach ($errors->all() as $error)
									<li>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
					@endif
					<div class="mb-3">
						{{ html()->label('Name', 'name') }}
						{{ html()->text('name')->class('form-control') }}
					</div>
					<div class="mb-3">
							{{ html()->label('Short Name', 'name_short') }}
							{{ html()->text('name_short')->class('form-control') }}
							<small>For display on Attendance Lists</small>
						</div>
					<div class="row">
						<div class="col-lg-6 col-sm-12 mb-3">
							{{ html()->label('Columns', 'columns') }}
							{{ html()->text('columns')->class('form-control') }}
						</div> 
						<div class="col-lg-6 col-sm-12 mb-3">
							{{ html()->label('Rows', 'rows') }}
							{{ html()->text('rows')->class('form-control') }}
						</div>
					</div>
					<div class="mb-3">
						{{ html()->label('Seating Plan Image', 'image') }}
						{{ html()->file('image')->class('form-control') }}
					</div>
					<button type="submit" class="btn btn-success w-100">Submit</button>
				</form>
			</div>
		</div>
	
	</div>
</div>

@endsection
