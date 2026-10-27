<nav class="navbar fixed-top lanops-header admin-topbar px-3">
	<div class="d-flex align-items-center">
		<button class="btn btn-link nav-link d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#admin-sidebar" aria-controls="admin-sidebar" aria-label="Toggle navigation">
			<i class="fa fa-bars"></i>
		</button>
		<a class="navbar-brand" href="/">{{ config('app.name') }} Admin</a>
	</div>
	<ul class="navbar-nav flex-row align-items-center gap-3">
		<li class="nav-item">
			<button type="button" class="btn btn-link nav-link color-mode-toggle" data-color-mode-toggle title="Switch light/dark mode" aria-label="Switch light/dark mode">
				<i class="fa fa-moon fa-fw color-mode-icon-light"></i>
				<i class="fa fa-sun fa-fw color-mode-icon-dark"></i>
			</button>
		</li>
		<li class="nav-item dropdown">
			<a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-user"></i> {{ $user->username }}</a>
			<ul class="dropdown-menu dropdown-menu-end position-absolute">
				<li><a class="dropdown-item" href="/account"><i class="fa fa-fw fa-user"></i> Profile</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item" href="/logout"><i class="fa fa-fw fa-power-off"></i> Log Out</a></li>
			</ul>
		</li>
	</ul>
</nav>
<!-- Sidebar: fixed on large screens, an offcanvas menu on small ones -->
<div class="offcanvas-lg offcanvas-start lanops-header admin-sidebar" tabindex="-1" id="admin-sidebar" aria-labelledby="admin-sidebar-label">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title" id="admin-sidebar-label">{{ config('app.name') }} Admin</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#admin-sidebar" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body p-0">
		<ul class="nav flex-column w-100">
			<li class="nav-item">
				<a class="nav-link @if (Request::is('admin')) active @endif" href="/admin"><i class="fa fa-gauge fa-fw"></i> Dashboard</a>
			</li>
			<li class="nav-item">
				<a class="nav-link @if (Request::is('admin/events*')) active @endif" href="/admin/events"><i class="fa fa-book fa-fw"></i> Events</a>
				<ul class="nav flex-column nav-second-level">
					@foreach (Helpers::getEvents('DESC', 5, true) as $event)
						<li class="nav-item">
							<a class="nav-link" href="/admin/events/{{ $event->slug }}">
								{{ $event->display_name }}
								@if ($event->status != 'PUBLISHED')
									- {{ $event->status }}
								@endif
							</a>
						</li>
					@endforeach
				</ul>
			</li>
			<li class="nav-item">
				<a class="nav-link @if (Request::is('admin/news*')) active @endif" href="/admin/news"><i class="fa fa-comment fa-fw"></i> News</a>
			</li>
			<li class="nav-item">
				<a class="nav-link @if (Request::is('admin/users*')) active @endif" href="/admin/users"><i class="fa fa-user fa-fw"></i> Users</a>
			</li>
			<li class="nav-item">
				<a class="nav-link @if (Request::is('admin/polls*')) active @endif" href="/admin/polls"><i class="fa fa-list fa-fw"></i> Polls</a>
			</li>
			<li class="nav-item">
				<a class="nav-link @if (Request::is('admin/venues*')) active @endif" href="/admin/venues"><i class="fa fa-building fa-fw"></i> Venues</a>
			</li>
			<li class="nav-item">
				<a class="nav-link @if (Request::is('admin/gallery*')) active @endif" href="/admin/gallery"><i class="fa fa-camera fa-fw"></i> Gallery</a>
			</li>
			<li class="nav-item">
				<a class="nav-link @if (Request::is('admin/purchases*')) active @endif" href="/admin/purchases"><i class="fa fa-credit-card fa-fw"></i> Purchases</a>
			</li>
			<li class="nav-item">
				<a class="nav-link @if (Request::is('admin/tournaments*')) active @endif" href="/admin/tournaments"><i class="fa fa-trophy fa-fw"></i> Tournaments</a>
			</li>
		</ul>
	</div>
</div>
