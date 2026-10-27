@extends ('layouts.admin-default')

@section ('page_title', 'News')

@section ('content')
<div class="row">
	<div class="col-lg-12">
		<h3 class="page-header">News</h3>
		<ol class="breadcrumb">
			<li class="active">
				News
			</li>
		</ol> 
	</div>
</div>

<div class="row">
	<div class="col-xs-12 col-sm-6">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-plus fa-fw"></i> Post News
			</div>
			<div class="panel-body">
				{{ html()->form('POST', url('/admin/news/'))->acceptsFiles()->open() }}
					<div class="form-group">
						{{ html()->label('Title', 'title') }}
						{{ html()->text('title')->class('form-control') }}
					</div>
					<div class="form-group">
						{{ html()->label('Article', 'article') }}
						{{ html()->textarea('article')->class('form-control wysiwyg-editor')->rows('10') }}
					</div>
					<div class="form-group">
						{{ html()->label('Tags', 'tags') }}<small> - Separate with a comma</small>
						{{ html()->text('tags', '')->class('form-control') }}
					</div>
					<button type="submit" class="btn btn-success btn-block">Submit</button> 
				</form>
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-comments fa-fw"></i> Comments
			</div>
			<div class="panel-body">
				<p>To Approve:</p>
				@if (!$commentsToApprove->isEmpty())
					@foreach ($commentsToApprove->reverse() as $comment)
						@if (!$comment->reviewed && !$comment->approved)
							<div class="alert alert-warning">
								{{ $comment->comment }}
								<span class="pull-right">
									<small>{{ $comment->user->username }} </small>
									<a href="/admin/news/{{ $comment->newsArticle->slug }}/comments/{{ $comment->id }}/approve">Approve</a> / 
									<a href="/admin/news/{{ $comment->newsArticle->slug }}/comments/{{ $comment->id }}/reject">Reject</a>
								</span>
							</div>
						@endif
					@endforeach
				@else
					<div class="alert alert-success">
						No Comments to Approve!
					</div>
				@endif
				<p>Reported:</p>
				@if (!$commentsReported->isEmpty())
					@foreach ($commentsReported->reverse() as $report)
						<div class="alert alert-danger">
							{{ $report->newsComment->comment }} - Reported by: {{ $report->user->username }}
							<span class="pull-right">
								<a href="/admin/news/{{ $report->newsComment->newsArticle->slug }}/comments/{{ $report->newsComment->id }}/reports/{{ $report->id }}/delete">Ignore</a> / 
								<a href="/admin/news/{{ $report->newsComment->newsArticle->slug }}/comments/{{ $report->newsComment->id }}/delete">Delete</a>
							</span>
						</div>
					@endforeach
				@else
					<div class="alert alert-success">
						No Reported Comments!
					</div>
				@endif
			</div>
		</div>
	</div>
	<div class="col-xs-12 col-sm-6">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-users fa-fw"></i> News
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-striped table-hover">
						<thead>
							<tr>
								<th>Title</th>
								<th>By</th>
								<th>Date</th>
								<th></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							@foreach ($newsArticles as $newsArticle)
								<tr>
									<td>
										{{ $newsArticle->title }}
									</td>
									<td>
										{{ $newsArticle->user->username }}
									</td>
									<td>
										{{ $newsArticle->created_at }}
									</td>
									<td width="15%">
										<a href="/admin/news/{{ $newsArticle->slug }}"><button type="button" class="btn btn-primary btn-sm btn-block">Edit</button></a>
									</td>
									<td width="15%">
										{{ html()->form('POST', url('/admin/news/' . $newsArticle->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
											@method('DELETE')
											<button type="submit" class="btn btn-danger btn-sm btn-block">Delete</button>
										</form>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
					{{ $newsArticles->links() }}
				</div>
			</div>  
		</div>
		
	</div>
</div>
 
@endsection