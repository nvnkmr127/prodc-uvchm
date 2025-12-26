<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Setting; // <-- Import the Setting model

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        try {
            // Fetch all settings and key them by their name for easy access
            $settings = Setting::all()->keyBy('key');
        } catch (\Throwable $e) {
            \Log::warning('AuthenticatedSessionController: Failed to load settings: ' . $e->getMessage());
            $settings = collect();
        }

        // Pass the settings to the login view
        return view('auth.login', compact('settings'));
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();

            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard', absolute: false));
        } catch (\Illuminate\Database\QueryException $e) {
            // Check for connection refusal or access denied
            if ($e->getCode() === 1045 || $e->getCode() === 2002) {
                return back()->withInput()->withErrors([
                    'email' => 'System is currently unavailable. Please contact support.',
                ]);
            }
            throw $e;
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
