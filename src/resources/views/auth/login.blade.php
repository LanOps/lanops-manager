@extends ('layouts.default')

@section ('page_title', 'Login to continue')

@section ('content')

    <div class="container">
        <div class="page-header">
            <h1>Please Login</h1> 
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-6">
                @if (in_array('standard', $activeLoginMethods))
                    <form method="POST" action="/login/standard" class="form-horizontal">
                        @csrf
                        <div class="mb-3 row">
                            <label for="email" class="col-sm-2 form-label">Email</label>
                            <div class="col-sm-10 @error('email') @enderror">
                                <input type="email" id="email" name="email" class="form-control" placeholder="Email address" required autofocus>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="password" class="col-sm-2 form-label">Password</label>
                            <div class="col-sm-10 @error('password') @enderror">
                                <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="offset-sm-2 col-sm-10">
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input type="checkbox" value="remember-me"> Remember me
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="offset-sm-2 col-sm-10">
                                <button class="btn btn-lg btn-primary w-100" type="submit">Sign in</button>
                            </div>
                        </div>
                    </form>
                    <div class="row">
                        <div class="offset-sm-2 col-sm-10">
                            <a href="/register/standard">
                                <button class="btn btn-lg btn-primary w-100">Register</button>
                            </a>
                        </div>
                        <div class="offset-sm-2 col-sm-10">
                            <a class="btn btn-link" href="/login/forgot">
                                Forgot Your Password?
                            </a>
                        </div>
                    </div>
                @endif
            </div>
            <div class="col-sm-12 col-md-6">
                <p>Use one of the login methods below to continue or <a href="/register/standard">register</a></p>
                @if (in_array('steam', $activeLoginMethods))
                    <a href="/login/steam">
                        <div class="">
                            <button class="btn btn-lg btn-primary w-100" type="submit"><img class="img img-fluid rounded login-icon-button" src="/images/login/steam.png"/>Steam</button>
                        </div>
                    </a>
                @endif
            </div>
        </div>
    </div>

@endsection