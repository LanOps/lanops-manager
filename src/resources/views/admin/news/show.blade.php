@extends ('layouts.admin-default')

@section ('page_title', 'News')

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="page-header">News</h3>
		<ol class="breadcrumb">
			<li>
				<a href="/admin/news/">News</a>
			</li>
			<li class="active">
				{{ $newsArticle->title }}
			</li>
		</ol> 
	</div>
</div>

<div class="row">
	<div class="col-xs-12 col-sm-8">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-pencil fa-fw"></i> Edit {{ $newsArticle->title }}
			</div>
			<div class="panel-body">
				{{ html()->form('POST', url('/admin/news/' . $newsArticle->slug))->acceptsFiles()->open() }}
					<div class="form-group">
						{{ html()->label('Title', 'title') }}
						{{ html()->text('title', $newsArticle->title)->class('form-control') }}
					</div>
					<div class="form-group">
						{{ html()->label('Article', 'article') }}
						{{ html()->textarea('article', $newsArticle->article)->class('form-control wysiwyg-editor')->rows('10') }}
					</div>
					<div class="form-group">
						{{ html()->label('Tags', 'tags') }}<small> - Separate with a comma</small>
						{{ html()->text('tags', $newsArticle->getTags())->class('form-control') }}
					</div>
					<button type="submit" class="btn btn-success btn-block">Submit</button> 
				</form>
				<hr>
				{{ html()->form('POST', url('/admin/news/' . $newsArticle->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
					@method('DELETE')
					<button type="submit" class="btn btn-danger btn-block">Delete</button>
				</form>
			</div>
		</div>
	</div>
	<div class="col-xs-12 col-sm-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-users fa-fw"></i> Stats
			</div>
			<div class="panel-body">
				<!-- // TODO -->
				To do
			</div>  
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-comments fa-fw"></i> Comments
			</div>
			<div class="panel-body">
				@foreach ($comments as $comment)
					@include ('layouts._partials._news.comment-warnings')
					@include ('layouts._partials._news.comment')
				@endforeach
				{{ $comments->links() }}
			</div>  
		</div>
	</div>
</div>
 
@endsection