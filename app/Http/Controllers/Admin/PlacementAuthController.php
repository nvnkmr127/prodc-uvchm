<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class PlacementAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('placement_officer.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        // Clean the phone number input (extract digits)
        $rawPhone = trim($request->phone);
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        $last10 = strlen($cleanPhone) >= 10 ? substr($cleanPhone, -10) : $cleanPhone;

        // Find user by phone (exact, clean, or matching last 10 digits)
        $user = User::where(function($q) use ($rawPhone, $cleanPhone, $last10) {
            $q->where('phone', $rawPhone)
              ->orWhere('phone', $cleanPhone);
            if (!empty($last10)) {
                $q->orWhere('phone', 'like', "%{$last10}");
            }
        })->first();

        if (!$user) {
            return back()->withErrors(['phone' => 'No account found matching this mobile number.']);
        }

        // Check if user has Placement Officer or Admin role
        if (!Role::where('name', 'Placement Officer')->exists()) {
            Role::create(['name' => 'Placement Officer']);
        }

        if (!$user->hasRole('Placement Officer') && !$user->hasRole('super-admin') && !$user->hasRole('college-admin') && !$user->hasRole('admin')) {
            if ($user->hasRole('staff') || $user->can('view students')) {
                $user->assignRole('Placement Officer');
            } else {
                return back()->withErrors(['phone' => 'Access denied. You are not authorized for Placement Portal.']);
            }
        }

        // Log the user in
        Auth::login($user);

        return redirect()->route('placement-portal.index');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('placement-officer.login');
    }
}
