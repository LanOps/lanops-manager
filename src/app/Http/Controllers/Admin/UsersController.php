<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

use App\Models\User;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class UsersController extends Controller
{
    /**
     * Show Users Index Page, optionally filtered by ?search=
     * @param  Request $request
     * @return View
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $users = User::query();
        if ($search !== '') {
            // Escape LIKE wildcards so "%" and "_" match literally
            $like = '%' . addcslashes($search, '\\%_') . '%';
            $users->where(function ($query) use ($like, $search) {
                $query->where('username', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('firstname', 'like', $like)
                    ->orWhere('surname', 'like', $like)
                    ->orWhereRaw("CONCAT(COALESCE(firstname, ''), ' ', COALESCE(surname, '')) LIKE ?", [$like])
                    ->orWhere('discord_username', 'like', $like);
                if (ctype_digit($search)) {
                    $query->orWhere('steamid', $search);
                }
            });
        }

        return view('admin.users.index')
            ->withUser(Auth::user())
            ->withSearch($search)
            ->withUsers($users->orderBy('id')->paginate(20)->withQueryString());
    }

    /**
     * Show User Page
     * @return View
     */
    public function show(User $user)
    {
        return view('admin.users.show')
            ->withUserShow($user)
            ->withPurchases($user->purchases()->paginate(10, ['*'], 'pu'));
    }

    /**
     * Update User
     * @param  User  $user
     * @return View
     */
    public function update(User $user, Request $request)
    {
        $rules = [
            'username'      => 'required|max:255|unique:users,username,' . $user->id,
        ];
        $messages = [
            'username.unique'       => 'Username must be unique',
            'username.required'     => 'Username is required',
        ];

        $this->validate($request, $rules, $messages);

        $user->username = $request->username;
        $user->username_nice    = strtolower(str_replace(' ', '-', $request->username));
        if (!$user->save()) {
            Session::flash('alert-danger', 'Cannot update user!');
            return Redirect::back();
        }
        Session::flash('alert-success', 'Successfully updated user!');
        return Redirect::back();
    }

    /**
     * Change a user's email address, e.g. to fix a typo that stops them verifying
     * @param  User    $user
     * @param  Request $request
     * @return Redirect
     */
    public function updateEmail(User $user, Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ], [
            'email.required' => 'Email is required.',
            'email.email'    => 'Email must be a valid Email Address.',
            'email.unique'   => 'That Email Address is already in use.',
        ]);

        if ($request->email === $user->email) {
            Session::flash('alert-info', 'That is already their email address.');
            return Redirect::back();
        }

        $sendVerification = $request->boolean('send_verification');
        $user->email = $request->email;
        // Either the user proves the new address by clicking the link, or the admin vouches for it
        $user->email_verified_at = $sendVerification ? null : now();
        if (!$user->save()) {
            Session::flash('alert-danger', 'Cannot update email address!');
            return Redirect::back();
        }
        if ($sendVerification) {
            $user->sendEmailVerificationNotification();
            Session::flash('alert-success', 'Email address changed. A verification link has been sent to the new address.');
            return Redirect::back();
        }
        Session::flash('alert-success', 'Email address changed and marked as verified.');
        return Redirect::back();
    }

    /**
     * Grant User Admin
     * @param  User  $user
     * @return View
     */
    public function grantAdmin(User $user)
    {
        if (!Auth::user()->admin) {
            Session::flash('alert-danger', 'You do not have permissions to do this!');
            return Redirect::back();
        }
        $user->admin = true;
        if (!$user->save()) {
            Session::flash('alert-danger', 'Cannot grant admin!');
            return Redirect::back();
        }
        Session::flash('alert-success', 'Successfully updated user!');
        return Redirect::back();
    }

    /**
     * Remove User Admin
     * @param  User  $user
     * @return View
     */
    public function removeAdmin(User $user)
    {
        if (!Auth::user()->admin) {
            Session::flash('alert-danger', 'You do not have permissions to do this!');
            return Redirect::back();
        }
        $user->admin = false;
        if (!$user->save()) {
            Session::flash('alert-danger', 'Cannot remove admin!');
            return Redirect::back();
        }
        Session::flash('alert-success', 'Successfully updated user!');
        return Redirect::back();
    }

    /**
     * Ban User
     * @param  User  $user
     * @return View
     */
    public function ban(User $user)
    {
        if (!Auth::user()->admin) {
            Session::flash('alert-danger', 'You do not have permissions to do this!');
            return Redirect::back();
        }
        $user->banned = true;
        if (!$user->save()) {
            Session::flash('alert-danger', 'Cannot ban user!');
            return Redirect::back();
        }
        Session::flash('alert-success', 'Successfully banned user!');
        return Redirect::back();
    }

    /**
     * Unban User
     * @param  User  $user
     * @return View
     */
    public function unban(User $user)
    {
        if (!Auth::user()->admin) {
            Session::flash('alert-danger', 'You do not have permissions to do this!');
            return Redirect::back();
        }
        $user->banned = false;
        if (!$user->save()) {
            Session::flash('alert-danger', 'Cannot unban user!');
            return Redirect::back();
        }
        Session::flash('alert-success', 'Successfully unbanned user!');
        return Redirect::back();
    }

    public function generalReferralCodes()
    {
        $users = User::all();
        foreach ($users as $user) {
            $user->referral_code = User::generateReferralCode();
            $user->save();
        }
    }
}
