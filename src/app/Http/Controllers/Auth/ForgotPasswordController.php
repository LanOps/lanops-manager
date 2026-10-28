<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Give the same answer whether or not the email has an account, or an
     * email was sent to it moments ago, so the form can't be used to find
     * out which emails are registered.
     * @param  Request $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        if (in_array($response, [Password::INVALID_USER, Password::RESET_THROTTLED], true)) {
            return $this->sendResetLinkResponse($request, Password::RESET_LINK_SENT);
        }
        return back()->withInput($request->only('email'))->withErrors(['email' => trans($response)]);
    }
}
