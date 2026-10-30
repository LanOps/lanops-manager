@extends ('layouts.admin-default')

@section ('page_title', 'Gallery')

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="pb-2 mb-3 border-bottom">Gallery</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item active">
				Gallery
			</li>
		</ol>
	</div>
</div>

<div class="row">
	<div class="col-lg-8">

		<div class="card">
			<div class="card-header">
				<i class="fa fa-table-list fa-fw"></i> Albums
			</div>
			<div class="card-body">
				<div class="dataTable_wrapper">
					<table width="100%" class="table table-striped table-hover" id="dataTables-example">
						<thead>
							<tr>
								<th>Name</th>
								<th>Description</th>
								<th>URL</th>
								<th>Status</th>
								<th># of Images</th>
								<th></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							@foreach ($albums as $album)
								<tr>
									<td>{{ $album->name }}</td>
									<td>{{ $album->description }}</td>
									<td>{{ $album->slug }}</td>
									<td>{{ $album->status }}</td>
									<td>{{ $album->getMedia('images')->count() }}</td>
									<td width="15%">
										<a href="/admin/gallery/{{ $album->slug }}">
											<button type="button" class="btn btn-primary btn-sm w-100">Edit</button>
										</a>
									</td>
									<td width="15%">
										{{ html()->form('POST', url('/admin/gallery/' . $album->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
											@method('DELETE')
											<button type="submit" class="btn btn-danger btn-sm w-100">Delete</button>
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
		<div class="card">
			<div class="card-header">
				<i class="fa fa-plus fa-fw"></i> Add Album
			</div>
			<div class="card-body">
				<div class="list-group">
					{{ html()->form('POST', url('/admin/gallery/'))->acceptsFiles()->open() }}
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
							{{ html()->label('Album Name', 'name') }}
							{{ html()->text('name')->class('form-control') }}
						</div> 
						<div class="mb-3">
							{{ html()->label('Description', 'description') }}
							{{ html()->textarea('description')->class('form-control')->rows('2') }}
						</div>
						<button type="submit" class="btn btn-success w-100">Submit</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

@endsection
