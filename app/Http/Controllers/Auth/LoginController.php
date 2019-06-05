<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function login(Request $request)
    {
        if ($this->attemptLogin($request)) {
            $user = $this->guard()->user();
            $user->api_token = Str::random(60);
            $user->save();

            return response()->json([
                'token' => $user->api_token,
                'name' => $user->name
            ], 200);
        }

        return $this->sendFailedLoginResponse($request);
        
    }

    protected function logout(Request $request)
    {
        $user = $request->user();
        $user->api_token = null;
        $user->save();
    }
}
