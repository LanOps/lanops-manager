@extends ('layouts.admin-default')

@section ('page_title', 'News')

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="pb-2 mb-3 border-bottom">News</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item">
				<a href="/admin/news/">News</a>
			</li>
			<li class="breadcrumb-item active">
				{{ $newsArticle->title }}
			</li>
		</ol> 
	</div>
</div>

<div class="row">
	<div class="col-12 col-sm-8">
		<div class="card">
			<div class="card-header">
				<i class="fa fa-pencil fa-fw"></i> Edit {{ $newsArticle->title }}
			</div>
			<div class="card-body">
				{{ html()->form('POST', url('/admin/news/' . $newsArticle->slug))->acceptsFiles()->open() }}
					<div class="mb-3">
						{{ html()->label('Title', 'title') }}
						{{ html()->text('title', $newsArticle->title)->class('form-control') }}
					</div>
					<div class="mb-3">
						{{ html()->label('Article', 'article') }}
						{{ html()->textarea('article', $newsArticle->article)->class('form-control wysiwyg-editor')->rows('10') }}
					</div>
					<div class="mb-3">
						{{ html()->label('Tags', 'tags') }}<small> - Separate with a comma</small>
						{{ html()->text('tags', $newsArticle->getTags())->class('form-control') }}
					</div>
					<button type="submit" class="btn btn-success w-100">Submit</button> 
				</form>
				<hr>
				{{ html()->form('POST', url('/admin/news/' . $newsArticle->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
					@method('DELETE')
					<button type="submit" class="btn btn-danger w-100">Delete</button>
				</form>
			</div>
		</div>
	</div>
	<div class="col-12 col-sm-4">
		<div class="card">
			<div class="card-header">
				<i class="fa fa-users fa-fw"></i> Stats
			</div>
			<div class="card-body">
				<!-- // TODO -->
				To do
			</div>  
		</div>
		<div class="card">
			<div class="card-header">
				<i class="fa fa-comments fa-fw"></i> Comments
			</div>
			<div class="card-body">
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