@extends ('layouts.default')

@section ('page_title', 'Register')

@section ('content')

<div class="container">
    <div class="page-header">
        <h1>Register Details</h1>
    </div>
    <div class="row">
        {{ html()->form('POST', url('/register/' . $loginMethod))->open() }}
            {{ csrf_field() }}
            {{ html()->hidden('method', $loginMethod)->class('form-control') }}
            @if ($loginMethod == "steam")
                {{ html()->hidden('avatar', $avatar)->class('form-control') }}
                {{ html()->hidden('steamid', $steamid)->class('form-control') }}
            @endif
            <div class="col-12 col-md-6">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="mb-3 @error('firstname') @enderror">
                            {{ html()->label('Firstname', 'firstname') }}
                            <input id="firstname" type="firstname" class="form-control" name="firstname" value="{{ old('firstname') }}" required autocomplete="firstname">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="mb-3 @error('surname') @enderror">
                            {{ html()->label('Surname', 'surname') }}
                            <input id="surname" type="surname" class="form-control" name="surname" value="{{ old('surname') }}" required autocomplete="surname">
                        </div>
                    </div>
                </div>
                <div class="mb-3 @error('username') @enderror">
                    {{ html()->label('Username', 'username') }}
                    <input id="username" type="username" class="form-control" name="username" value="{{ old('username') }}" required autocomplete="username">
                </div>
                @if ($loginMethod == "standard")
                    <div class="mb-3 @error('email') @enderror">
                        {{ html()->label('E-Mail', 'email') }}
                        <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autocomplete="email">
                    </div>
                    <div class="mb-3 @error('password1') @enderror">
                        {{ html()->label('Password', 'password1') }}
                         <input id="password1" type="password" class="form-control" name="password1" required autocomplete="new-password">
                    </div>
                    <div class="mb-3 @error('password2') @enderror">
                        {{ html()->label('Confirm Password', 'password2') }}
                        <input id="password2" type="password" class="form-control" name="password2" required autocomplete="new-password">
                    </div>
                    <input id="url" type="hidden" class="form-control" name="url">
                @endif
                @if ($loginMethod == "steam")
                    <div class="mb-3">
                        {{ html()->label('Steam Name', 'steamname') }}
                        {{ html()->text('steamname', Helpers::getSteamProfile($steamid)['name'])->class('form-control')->disabled() }}
                    </div>
                @endif
            </div>
            <div class="col-12 col-md-6">
                @include ('layouts._partials._terms.registration')
                <h5>By Clicking on Confirm you are agreeing to the Terms and Conditions as set by {!! config('app.name') !!}</h5>
                <div class="mb-3">
                    <center>
                        <img class="img img-fluid mb-2" src="{{ captcha_src('default'); }}" alt="captcha">
                    </center>
                </div>
                <div class="margin-top-2"></div>
                <div class="mb-3 @error('captcha') @enderror">
                    <input type="text" name="captcha" class="form-control @error('captcha') is-invalid @enderror" placeholder="Please Insert Captcha">
                </div>
                 
                <button type="submit" class="btn w-100 btn-primary">Register</button>
            </div>
        </form>
    </div>
</div>

@endsection