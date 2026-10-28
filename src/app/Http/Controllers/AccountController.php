<?php

namespace App\Http\Controllers;

use Auth;
use Session;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Http;

class AccountController extends Controller
{
    /**
     * Show Account Index Page
     * @return View
     */
    public function index()
    {
        $user = Auth::user();
        $purchases = $user->purchases()->latest()->paginate(5, ['*'], 'pu');
        $tickets = $user->eventParticipants()->latest()->paginate(5, ['*'], 'ti');

        $state = bin2hex(openssl_random_pseudo_bytes(12));
        Session::put('discordoauth', $state);
        $discordLinkUrl = 'https://discordapp.com/oauth2/authorize?response_type=code&client_id=' . config('app.discord_client_id') . '&redirect_uri=' . config('app.discord_redirect_url') . '&scope=' . config('app.discord_scope') . "&state=" . $state;

        return view("accounts.index")
            ->withUser($user)
            ->withPurchases($purchases)
            ->withEventParticipants($tickets)
            ->withDiscordLinkUrl($discordLinkUrl)
        ;
    }

    public function unlinkDiscord(Request $request)
    {
        $user = Auth::user();
        $user->discord_id = null;
        $user->discord_avatar = null;
        $user->discord_username = null;

        if (!$user->save()) {
            Session::flash('alert-danger', 'Something went wrong, please try again!');
        }

        return Redirect::to('account');
    }

    public function linkDiscord(Request $request)
    {
        $state = Session::pull('discordoauth');
        if (!$state || !is_string($request->input('state')) || !hash_equals($state, $request->input('state'))) {
            Session::flash('alert-danger', 'Discord link expired, please try again.');
            return Redirect::to('account');
        }

        $response = Http::asform()->withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://discord.com/api/oauth2/token", [
                "client_id" => config('app.discord_client_id'),
                "client_secret" => config('app.discord_client_secret'),
                "grant_type" => "authorization_code",
                "code" => $request->input('code'),
                "redirect_uri" => config('app.discord_redirect_url')
            ]);

        if ($response->status() != 200) {
            Session::flash('alert-danger', 'Somewent went wrong!');
            return Redirect::to('account');
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => ' Bearer ' . $response['access_token']
        ])->get("https://discord.com/api/users/@me");

        
        if ($response->status() != 200) {
            Session::flash('alert-danger', 'Somewent went wrong!');
            return Redirect::to('account');
        }

        $user = Auth::user();
        $user->discord_id = $response['id'];
        $user->discord_username = $response['global_name'];
        $user->discord_avatar = $response['avatar'];

        if (!$user->save()) {
            Session::flash('alert-danger', 'Something went wrong, please try again!');
        }
        return Redirect::to('account');
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $rules = [
            'firstname'     => 'filled',
            'surname'       => 'filled',
            'email'         => 'filled|email|unique:users,email,' . $user->id,
            'password1'     => 'same:password2',
            'password2'     => 'same:password1',
        ];
        $messages = [
            'email.unique'      => 'That Email Address is already in use.',
            'email.filled'      => 'Email Cannot be blank.',
            'firstname.filled'  => 'Firstname Cannot be blank.',
            'surname.filled'    => 'Surname Cannot be blank.',
            'email.email'       => 'Email must be a valid Email Address.',
            'password1.same'    => 'Passwords must be the same.',
            'password2.same'    => 'Passwords must be the same.',
        ];
        $this->validate($request, $rules, $messages);

        $changingEmail = $request->filled('email') && $request->email !== $user->email;
        $changingPassword = isset($request->password1) && $request->password1 != null;

        // Email and password changes need the current password, when the account has one
        if (($changingEmail || $changingPassword) && $user->password != null &&
            !Hash::check((string) $request->current_password, $user->password)
        ) {
            return Redirect::back()->withErrors(['current_password' => 'Your current password is incorrect.']);
        }

        if ($changingPassword) {
            $rules = [
                'password1'     => 'same:password2|min:8',
                'password2'     => 'same:password1|min:8',
            ];
            $messages = [
                'password1.same'    => 'Passwords must be the same.',
                'password1.min'     => 'Password must be atleast 8 characters long.',
                'password2.same'    => 'Passwords must be the same.',
                'password2.min'     => 'Password must be atleast 8 characters long.',
            ];
            $this->validate($request, $rules, $messages);
            $user->password = Hash::make($request->password1);
        }

        if ($request->filled('email')) {
            $user->email = $request->email;
        }
        if ($request->filled('firstname')) {
            $user->firstname = $request->firstname;
        }
        if ($request->filled('surname')) {
            $user->surname = $request->surname;
        }
        if ($changingEmail) {
            $user->email_verified_at = null;
        }

        if (!$user->save()) {
            return Redirect::back()->withFail("Oops, Something went Wrong.");
        }
        if ($changingEmail) {
            $user->sendEmailVerificationNotification();
        }
        if ($changingPassword) {
            // Anyone else signed in to this account (other devices, remember-me cookies) is signed out
            Auth::logoutOtherDevices($request->password1);
        }
        return Redirect::back()->withSuccess('Account successfully updated!');
    }

    /**
     * Delete Account
     * @return Redirect
     */
    public function destroy()
    {
        $user = Auth::user();
        if ($user && $user->delete()) {
            Session::flash('alert-success', 'Account Deleted!');
            return Redirect::to('/');
        }
        Session::flash('alert-danger', 'Could not delete Account!');
        return Redirect::to('/account');
    }
}
