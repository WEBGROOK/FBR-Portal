<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            AuditService::log('LOGIN_SUCCESS', 'User', Auth::id());
            return redirect()->intended(route('dashboard'));
        }

        AuditService::log('LOGIN_FAILED', 'User', null, ['email' => $request->email]);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'seller_ntn' => ['required', 'string', 'regex:/^[0-9]{7}-[0-9]{1}$/'],
            'pos_id' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'seller_ntn.regex' => 'Seller NTN format must be 7 digits followed by check digit (e.g. 1234567-8).',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'seller_ntn' => $request->seller_ntn,
            'pos_id' => $request->pos_id,
            'password' => Hash::make($request->password),
            'role' => 'ADMIN',
        ]);

        Auth::login($user);
        AuditService::log('USER_REGISTERED', 'User', $user->id);

        return redirect()->route('dashboard')->with('success', "Welcome {$user->name}! Your shop account has been created with POS ID {$user->pos_id}.");
    }

    public function logout(Request $request)
    {
        AuditService::log('LOGOUT', 'User', Auth::id());

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
