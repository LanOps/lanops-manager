<?php

namespace App\Http\Controllers\Auth;

use Validator;

use App\Models\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

use Carbon\Carbon;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    public function authenticated(Request $request, $user)
    {
        $user->last_login = Carbon::now()->toDateTimeString();
        $user->save();
    }

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|confirmed|min:6',
        ]);
    }

    /**
     * Prompt Login User
     * @return Redirect
     */
    public function prompt()
    {
        return view('auth.login')
            ->withActiveLoginMethods(['standard', 'steam']);
    }

    /**
     * Show Register User Page
     * @param  User   $user
     */
    public function showRegister($method)
    {
        switch ($method) {
            case 'steam':
                if (!Session::has('user')) {
                    return Redirect::to('/');
                }

                $user = Session::get('user');
                
                if (is_null($user['steamid'])) {
                    return redirect('/'); // redirect to site
                }
                return view('auth.register', $user)->withLoginMethod('steam');
                break;
            default:
                return view('auth.register')->withLoginMethod('standard');
                break;
        }
    }

    /**
     * Register User Page
     * @param  User   $user
     * @param  Request   $request
     * @return Redirect
     */
    public function register($method, Request $request, User $user)
    {
        if (isset($request->url) && $request->url != '') {
            return Redirect::back();
        }
        switch ($method) {
            case 'steam':
                // The Steam account comes from the Steam login, never from the form
                $steamUser = Session::get('user');
                if (empty($steamUser['steamid'])) {
                    return Redirect::to('/login');
                }
                $this->validate($request, [
                    'firstname' => 'required|string',
                    'surname'   => 'required|string',
                    'username'  => 'required|unique:users,username',
                ]);
                if (User::where('steamid', $steamUser['steamid'])->exists()) {
                    Session::forget('user');
                    Session::flash('alert-danger', 'That Steam account is already registered. Please log in.');
                    return Redirect::to('/login');
                }
                $user->avatar               = $steamUser['avatar'] ?? null;
                $user->steamid              = $steamUser['steamid'];
                // No email Verification needed - just add the email_verified_at
                $user->email_verified_at    = new \DateTime('NOW');
                break;
            default:
                $rules = [
                    'email'         => 'required|filled|email|unique:users,email',
                    'password1'     => 'required|same:password2|min:8',
                    'username'      => 'required|unique:users,username',
                    'firstname'     => 'required|string',
                    'surname'       => 'required|string',
                    'captcha'       => 'required|captcha',
                ];
                $messages = [
                    'username.unique'       => 'Username must be unique',
                    'username.required'     => 'Username is required',
                    'email.filled'          => 'Email Cannot be blank.',
                    'email.required'        => 'Email is required.',
                    'email.email'           => 'Email must be a valid Email Address.',
                    'email.unique'          => 'Email must be unique.',
                    'password1.same'        => 'Passwords must be the same.',
                    'password1.required'    => 'Password is required.',
                    'password1.min'         => 'Password must be atleast 8 characters long.',
                    'captcha'               => 'Captcha is incorrect.',
                ];
                $this->validate($request, $rules, $messages);
                $user->email          = $request->email;
                $user->password       = Hash::make($request->password1);
                break;
        }
       
        $user->firstname        = $request->firstname;
        $user->surname          = $request->surname;
        $user->username         = $request->username;
        $user->username_nice    = strtolower(str_replace(' ', '-', $request->username));
        $user->referral_code = User::generateReferralCode();

        // Fix to make first user admin
        if (User::count() == 0) {
            $user->admin = true;
        }

        if (!$user->save()) {
            Auth::logout();
            return Redirect('/')->withError('Something went wrong. Please Try again later');
        }
        Session::forget('user');
        Auth::login($user, true);
        if ($method == 'standard') {
            $user->sendEmailVerificationNotification();
        }
        return Redirect('/account');
    }
}
