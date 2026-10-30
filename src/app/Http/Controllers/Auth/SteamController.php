<?php

namespace App\Http\Controllers\Auth;

use Auth;

use Session;

use App\Models\User;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Steam\OpenIDValidationException;

use Carbon\Carbon;

class SteamController extends Controller
{
    /**
     * Login User
     * @param  Request $request
     * @return Redirect
     */
    public function login(Request $request)
    {
        $steam = Socialite::driver('steam')
            ->redirectUrl(url('/login/steam', [], config('services.steam.force_https')));
        // Steam's OpenID response arrives as query params (PHP turns "openid.x" into "openid_x")
        if (!$request->has('openid_assoc_handle')) {
            return $steam->redirect(); // redirect to Steam login page
        }
        try {
            $steamUser = $steam->user();
        } catch (OpenIDValidationException $e) {
            Session::flash('alert-danger', 'Steam login failed. Please try again.');
            return Redirect('/login');
        }
        $info = (object) $steamUser->getRaw();
        if (!empty($info->steamid)) {
            $user = User::where('steamid', $info->steamid)->first();
            if (!is_null($user)) {
                if ($user->banned) {
                    Session::flash('alert-danger', 'You have been banned!');
                    return Redirect::back()->withError('You have been banned.');
                }
                //username found... Log user in
                Auth::login($user, true);
                //Check if the user has changed their steam details
                $steam_changes = false;
                if ($info->avatarfull != $user->avatar) {
                    $user->avatar = $info->avatarfull;
                    $steam_changes = true;
                }
                $user->last_login = Carbon::now()->toDateTimeString();
                $user->save();
                // Back to where they were sent to log in from (e.g. a gift link), like the email login
                return redirect()->intended('/');
            } else {
                $user = [
                        'avatar'        => $info->avatarfull,
                        'steamid'       => $info->steamid,
                ];
                Session::put('user', $user);
                Session::save();
                return Redirect('/register/steam');
            }
        }
        Session::flash('alert-danger', 'Steam login failed. Please try again.');
        return Redirect('/login');
    }
}
