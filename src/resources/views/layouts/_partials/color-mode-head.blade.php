{{-- Set the colour mode before the page paints, so dark mode users don't see a white flash --}}
<script>
	(function () {
		var mode = null;
		try { mode = localStorage.getItem('color-mode'); } catch (e) {}
		if (!mode) {
			mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
		}
		document.documentElement.setAttribute('data-bs-theme', mode);
	})();
</script>
