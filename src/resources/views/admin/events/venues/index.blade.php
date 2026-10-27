@extends ('layouts.admin-default')

@section ('page_title', 'Venues')

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="page-header">Venues</h3>
		<ol class="breadcrumb">
			<li class="active">
				Venues
			</li>
		</ol>
	</div>
</div>

<div class="row">
	<div class="col-lg-8">

		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-th-list fa-fw"></i> Venues
			</div>
			<div class="panel-body">
				<div class="dataTable_wrapper">
					<table width="100%" class="table table-striped table-hover" id="dataTables-example">
						<thead>
							<tr>
								<th>Name</th>
								<th>Address</th>
								<th></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							@foreach ($venues as $venue)
								<tr class="table-row" data-href="/admin/venues/{{ $venue->slug }}" class="odd gradeX">
									<td>{{ $venue->display_name }}</td>
									<td>
										<address>
										<strong>{{ $venue->address_1 }}</strong>,<br>
											@if ($venue->address_2){{ $venue->address_2 }},<br>@endif
											@if ($venue->address_street){{ $venue->address_street }},<br>@endif
											@if ($venue->address_city){{ $venue->address_city }},<br>@endif
											@if ($venue->address_postcode){{ $venue->address_postcode }},<br>@endif
											@if ($venue->address_country){{ $venue->address_country }}@endif
										</address>
									</td>
									<td width="15%">
										<a href="/admin/venues/{{ $venue->slug }}"><button type="button" class="btn btn-primary btn-sm btn-block">Edit</button></a>
									</td>
									<td width="15%">
										{{ html()->form('POST', url('/admin/venues/' . $venue->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
											@method('DELETE')
											<button type="submit" class="btn btn-danger btn-sm btn-block">Delete</button>
										</form>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>  
		</div>

	</div>
	<div class="col-lg-4">

		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-plus fa-fw"></i> Add Venue
			</div>
			<div class="panel-body">
				{{ html()->form('POST', url('/admin/venues'))->acceptsFiles()->open() }}
					@if ($errors->any())
					  	<div class="alert alert-danger">
					        <ul>
					          	@foreach ($errors->all() as $error)
					            	<li>{{ $error }}</li>
					          	@endforeach
					        </ul>
					  	</div>
					@endif
					<div class="form-group">
						{{ html()->label('Venue Name', 'name') }}
						{{ html()->text('name', '')->class('form-control') }}
					</div>
					<div class="form-group">
						{{ html()->label('Address Line 1', 'address_1') }}
						{{ html()->text('address_1', '')->class('form-control') }}
					</div>
					<div class="form-group">
						{{ html()->label('Address Line 2', 'address_2') }}
						{{ html()->text('address_2', '')->class('form-control') }}
					</div>
					<div class="row">
						<div class="col-lg-6 col-sm-12 form-group">
							{{ html()->label('Address Street', 'address_street') }}
							{{ html()->text('address_street', '')->class('form-control') }}
						</div>
						<div class="col-lg-6 col-sm-12 form-group">
							{{ html()->label('Address City', 'address_city') }}
							{{ html()->text('address_city', '')->class('form-control') }}
						</div>
					</div>
					<div class="row">
						<div class="col-lg-6 col-sm-12 form-group">
							{{ html()->label('Address Postcode', 'address_postcode') }}
							{{ html()->text('address_postcode', '')->class('form-control') }}
						</div>
						<div class="col-lg-6 col-sm-12 form-group">
							{{ html()->label('Address Country', 'address_country') }}
                            <select class="form-control @error('address_country') is-invalid @enderror" name="address_country" id="address_country" aria-describedby="address_country" value="{{ old('address_country', 'GB') }}">
                                @foreach (Helpers::getSelectCountries() as $country)
                                    <option value="{{ $country }}" {{ ( $country == old('address_country', 'United Kingdom')) ? 'selected' : '' }}> 
                                        {{ $country }} 
                                    </option>
                                @endforeach
                            </select>
						</div>
					</div>
					<div class="form-group">
						{{ html()->label('Venue Images', 'images') }}
						{{ html()->file('images[]')->class('form-control')->multiple() }}
					</div>
					<button type="submit" class="btn btn-success btn-block">Submit</button>
				</form>
			</div>
		</div>

	</div>
</div>

@endsection