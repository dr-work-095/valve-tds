<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TenantSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'title' => 'Sign in to your workspace',
            'action' => route('login.store'),
            'admin' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        tenancy()->end();

        if (! Auth::guard('web')->attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'The provided credentials are invalid or the account is inactive.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        tenancy()->end();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
