<div class="row">
	<div class="col-2">
		<x-avatar :member="$comment->user" class="img-fluid rounded img-small news-post-comment-image" />
		<p class="news-post-comment-image-text">{{ $comment->user->username }}</p>
	</div>
	<div class="col-10">
		<p>{{ $comment->comment }}</p>
		<span class="text-muted"><small>Posted on: {{ $comment->created_at }}</small></span>
		@if (Auth::user() && Auth::id() == $comment->user_id) 
			<a href="" onclick="editComment(@js($comment->comment), @js($comment->id))" data-bs-toggle="modal" data-bs-target="#editCommentModal">Edit Comment</a> /
		@endif
		@if (Auth::user() && (Auth::user()->getAdmin() || $comment->user_id == Auth::id()))
			@php
				$postUrl = "";
			@endphp
			@if (Auth::user()->getAdmin())
				@php
					$postUrl = "/admin";
				@endphp
			@endif
			<x-action-button action="{{ $postUrl }}/news/{{ $comment->newsArticle->slug }}/comments/{{ $comment->id }}/delete">Delete Comment</x-action-button>
		@endif
		@if ($comment->approved && $comment->reviewed)
			@if (Auth::user() && !$comment->reports->pluck('user_id')->contains(Auth::id()) && $comment->user_id != Auth::id())
				@if ((Auth::user() && (Auth::user()->getAdmin() || $comment->user_id == Auth::id())) || (Auth::user() && Auth::id() == $comment->user_id)) 
					/
				@endif
				<x-action-button action="/news/{{ $newsArticle->slug }}/comments/{{ $comment->id }}/report">Report Comment</x-action-button>
			@endif
		@endif
	</div>
</div>


<!-- Edit Comment Modal -->
<div class="modal fade" id="editCommentModal" tabindex="-1" role="dialog" aria-labelledby="editCommentModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="editCommentModalLabel">Edit Comment</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			@if (Auth::user())
				{{ html()->form('POST', url('/news/' . $newsArticle->slug . '/comments'))->id('edit_comment_modal_form')->open() }}
					<div class="modal-body">
						<div class="mb-3">
							{{ html()->textarea('comment_modal', '')->class('form-control')->rows('4')->placeholder('Post a Comment') }}
						</div>
						<div class="modal-footer">
							<button type="submit" class="btn btn-success">Edit</button>
						</div>
					</div>
				</form>
			@else
				<div class="modal-body">
					<p>Please log in to post a Comment</p>
				</div>
			@endif
		</div>
	</div>
</div>

<script>
	function editComment(comment, comment_id)
	{
		console.log(comment_id);
		$("#comment_modal").val(comment);
		$("#edit_comment_modal_form").prop('action', '/news/{{ $newsArticle->slug }}/comments/' + comment_id);
	}
</script>