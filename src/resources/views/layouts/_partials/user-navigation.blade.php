<li class="nav-item dropdown">
	<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">{{ $user->username }}</a>
	<ul class="dropdown-menu dropdown-menu-end">
		@if ( $user->admin == 1 )
			<li><a class="dropdown-item" href="/admin">Admin</a></li>
		@endif
		<li><a class="dropdown-item" href="/account">Profile</a></li>
		<li><hr class="dropdown-divider"></li>
		<li>
			<form method="POST" action="/logout">
				@csrf
				<button type="submit" class="dropdown-item">Logout</button>
			</form>
		</li>
	</ul>
</li>
