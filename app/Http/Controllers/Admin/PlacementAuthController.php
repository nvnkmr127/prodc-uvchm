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
            'pin' => 'required|string',
        ]);

        // Clean the phone number input (remove spaces, etc if needed)
        $phone = trim($request->phone);
        $pin = trim($request->pin);

        // Find user by phone
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return back()->withErrors(['phone' => 'No faculty found with this phone number.']);
        }
        
        if ($user->placement_pin !== $pin) {
            return back()->withErrors(['pin' => 'Invalid PIN.']);
        }

        // Check if user has the Placement Officer role.
        // Also fallback: we check if role exists, if not create it (to avoid crashes for the user initially)
        if (!Role::where('name', 'Placement Officer')->exists()) {
            Role::create(['name' => 'Placement Officer']);
        }

        if (!$user->hasRole('Placement Officer')) {
            // For testing convenience, if they don't have it, we just check if they are staff. 
            // In a strict environment, we only allow Placement Officers.
            if ($user->hasRole('super-admin') || $user->hasRole('staff')) {
                 return back()->withErrors(['phone' => 'This phone number belongs to a staff member, but they are not assigned the "Placement Officer" role. Please ask an admin to assign this role.']);
            }
            return back()->withErrors(['phone' => 'Access denied. You are not a Placement Officer.']);
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
