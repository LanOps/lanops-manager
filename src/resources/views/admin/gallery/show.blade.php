@extends ('layouts.admin-default')

@section ('page_title', 'Gallery')

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="page-header">Gallery - {{ $album->name }}</h3>
		<ol class="breadcrumb">
			<li>
				<a href="/admin/gallery">Gallery</a>
			</li>
			<li class="active">
				{{ $album->name }}
			</li>
		</ol> 
	</div>
</div>

<div class="row">
	<div class="col-lg-8">

		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-image fa-fw"></i> Images
			</div>
			<div class="panel-body">
				<div class="dataTable_wrapper">
					<table width="100%" class="table table-striped table-hover" id="dataTables-example">
						<thead>
							<tr>
								<th>Image</th>
								<th>Added</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							@foreach ($images as $image)
								<tr>
									<td class=" col-xs-3">
										<img class="img-responsive img-thumbnail" src="{{ $image->getUrl('optimized') }}">
									</td>
									<td>
										{{ $image->created_at }}
									</td>
									{{ html()->form('POST', url('/admin/gallery/' . $album->slug . '/' . $image->id))->acceptsFiles()->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
										@method('DELETE')
										<td width="15%">
											<button type="submit" class="btn btn-danger btn-sm btn-block">Delete</button>
										</td>
									</form>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
				<!-- /.table-responsive -->
			</div>
		</div>

	</div>
	<div class="col-lg-4">

		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-upload fa-fw"></i> Add Images
			</div>
			<div class="panel-body">
				{{ html()->form('POST', url('/admin/gallery/' . $album->slug . '/upload'))->acceptsFiles()->open() }}
					{{ csrf_field() }}
					<div class="form-group">
						{{ html()->label('Select Images', 'images') }}
						{{ html()->file('images[]')->id('images')->class('form-control')->multiple() }}
					</div>
					<button type="submit" class="btn btn-primary btn-block">Upload</button>
				</form>
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-wrench fa-fw"></i> Settings
			</div>
			<div class="panel-body">
				<div class="list-group">
					{{ html()->form('POST', url('/admin/gallery/' . $album->slug))->open() }}
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
							{{ html()->label('Album Name', 'name') }}
							{{ html()->text('name', $album->name)->class('form-control') }}
						</div> 
						<div class="form-group">
							{{ html()->label('Description', 'description') }}
							{{ html()->textarea('description', $album->description)->class('form-control')->rows('2') }}
						</div>
						<div class="row">
							<div class="col-lg-6 col-sm-12 form-group">
								{{ html()->label('Status', 'status') }}
								{{ 
									html()->select('status', array(
											'draft'=>'Draft',
											'published'=>'Published',
										), strtolower($album->status))->class('form-control')
								}}
							</div>
							 <div class="col-lg-6 col-sm-12 form-group">
								{{ html()->label('Event', 'event_id') }}
								{{ html()->select('event_id', Helpers::getEventNames('DESC', 0), strtolower($album->event_id))->class('form-control') }}
							</div>
						</div>
						<button type="submit" class="btn btn-success btn-block">Submit</button>
					</form>
					<hr>
					{{ html()->form('POST', url('/admin/gallery/' . $album->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
						@method('DELETE')
						<button type="submit" class="btn btn-danger btn-block">Delete</button>
					</form>
				</div>
			</div>
		</div>

	</div>
</div>

<script type="text/javascript">
	$( function() {
		$( "#start_date" ).datepicker();
		$( "#end_date" ).datepicker();
	});
</script>

@endsection