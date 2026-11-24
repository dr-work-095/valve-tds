<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'title' => 'Super Admin sign in',
            'action' => route('admin.login.store'),
            'admin' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        tenancy()->end();

        if (! Auth::guard('admin')->attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'The provided credentials are invalid or the account is inactive.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        return redirect()->route('admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        tenancy()->end();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
