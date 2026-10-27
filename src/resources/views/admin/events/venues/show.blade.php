@extends ('layouts.admin-default')

@section ('page_title', $venue->display_name)

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="page-header">Venues</h3>
		<ol class="breadcrumb">
			<li>
				<a href="/admin/venues">Venues</a>
			</li>
			<li class="active">
				{{ $venue->display_name }}
			</li>
		</ol>
	</div>
</div>

<div class="row">
	<div class="col-lg-8">

		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-wrench fa-fw"></i> Settings
			</div>
			<div class="panel-body">
				{{ html()->form('POST', url('/admin/venues/' . $venue->slug))->acceptsFiles()->open() }}
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
						{{ html()->text('name', $venue->display_name)->class('form-control') }}
					</div>
					<div class="row">
						<div class="col-lg-6 col-sm-12 form-group">
							{{ html()->label('Address Line 1', 'address_1') }}
							{{ html()->text('address_1', $venue->address_1)->class('form-control') }}
						</div>
						<div class="col-lg-6 col-sm-12 form-group">
							{{ html()->label('Address Line 2', 'address_2') }}
							{{ html()->text('address_2', $venue->address_2)->class('form-control') }}
						</div>
					</div>
					<div class="row">
						<div class="col-lg-6 col-sm-12 form-group">
							{{ html()->label('Address Street', 'address_street') }}
							{{ html()->text('address_street', $venue->address_street)->class('form-control') }}
						</div>
						<div class="col-lg-6 col-sm-12 form-group">
							{{ html()->label('Address City', 'address_city') }}
							{{ html()->text('address_city', $venue->address_city)->class('form-control') }}
						</div>
					</div>
					<div class="row">
						<div class="col-lg-6 col-sm-12 form-group">
							{{ html()->label('Address Postcode', 'address_postcode') }}
							{{ html()->text('address_postcode', $venue->address_postcode)->class('form-control') }}
						</div>
						<div class="col-lg-6 col-sm-12 form-group">
							{{ html()->label('Address Country', 'address_country') }}
                            <select class="form-control @error('address_country') is-invalid @enderror" name="address_country" id="address_country" aria-describedby="address_country" value="{{ old('address_country', 'GB') }}">
                                @foreach (Helpers::getSelectCountries() as $country)
                                    <option value="{{ $country }}" {{ ( $country == $venue->address_country) ? 'selected' : '' }}> 
                                        {{ $country }} 
                                    </option>
                                @endforeach
                            </select>
						</div>
					</div>
					<button type="submit" class="btn btn-success btn-block">Submit</button>
				</form>
				<hr>
				{{ html()->form('POST', url('/admin/venues/' . $venue->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
					@method('DELETE')
					<button type="submit" class="btn btn-danger btn-block">Delete</button>
				</form>
			</div>  
		</div>

	</div>
	<div class="col-lg-4">

		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-plus fa-fw"></i> Add Images
			</div>
			<div class="panel-body">
				{{ html()->form('POST', url('/admin/venues/' . $venue->slug))->acceptsFiles()->open() }}
					<div class="form-group">
						{{ html()->label('Venue Images', 'images') }}
						{{ html()->file('images[]')->class('form-control')->multiple() }}
					</div>
					<button type="submit" class="btn btn-success btn-block">Upload</button>
				</form>
			</div>
		</div>

		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-image fa-fw"></i> Images
			</div>
			<div class="panel-body">
				@foreach ($venue->getMedia() as $image)
					<div class="row">
						<div class="col-lg-12 col-sm-12">
							<img class="img img-responsive" src="{{ $image->getUrl() }}" />
						</div>
						<div class="col-lg-12 col-sm-12">
							<br>
							{{ html()->form('POST', url('/admin/venues/' . $venue->slug . '/' . $image->id))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
								@method('DELETE')
								<button type="submit" class="btn btn-danger">Delete</button>
							</form>
						</div>
					</div>
					<hr>
				@endforeach
			</div>
		</div>

	</div>
</div>

@endsection