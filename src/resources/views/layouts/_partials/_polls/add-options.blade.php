<div class="mb-3" id="poll_options">
	<div class="mb-3">
		{{ html()->text('options[]', '')->class('form-control') }}
	</div>
</div>
<button type="button" id="poll_options_add" class="btn btn-outline-secondary w-100">Extra Option</button> 
<script type="text/javascript">
	(function() {
	    var max_fields = 20;
	    var wrapper = document.getElementById('poll_options');
	    var x = 1;

	    document.getElementById('poll_options_add').addEventListener('click', function(e) {
	        e.preventDefault();
	        if (x < max_fields) {
	            x++;
	            wrapper.insertAdjacentHTML('beforeend', '<div class="mb-3">{{ html()->text("options[]", "")->class("form-control") }}</div>');
	        } else {
	            alert('You Reached the limits')
	        }
	    });
	})();
</script>
