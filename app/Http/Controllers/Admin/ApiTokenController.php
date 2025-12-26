<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;

class ApiTokenController extends Controller
{
    /**
     * Display a listing of API tokens and users who can have tokens.
     */
    public function index()
    {
        // Get all users with admin, staff, or college-admin roles
        $roleNames = ['admin', 'staff', 'college-admin', 'super-admin', 'accountant'];

        // Check if roles exist first to avoid errors
        $existingRoles = Role::whereIn('name', $roleNames)->pluck('name')->toArray();

        if (empty($existingRoles)) {
            // If no roles exist, show empty collection
            $users = collect();
        } else {
            $users = User::role($existingRoles)->orderBy('name')->get();
        }

        // Get all existing tokens with their associated users
        $tokens = PersonalAccessToken::with('tokenable')->latest()->get();

        return view('admin.api_tokens.index', compact('users', 'tokens'));
    }

    /**
     * Store a newly created API token.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'token_name' => 'required|string|max:255|unique:personal_access_tokens,name',
            'abilities' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $user = User::findOrFail($request->user_id);

        // Check if user has appropriate role
        if (!$user->hasAnyRole(['admin', 'staff', 'college-admin', 'super-admin', 'accountant'])) {
            return redirect()->back()
                ->withErrors(['user_id' => 'Selected user does not have permission to create API tokens.']);
        }

        // Create token with abilities and expiration
        $abilities = $request->abilities ?? ['*']; // Default to all abilities
        $token = $user->createToken(
            $request->token_name,
            $abilities,
            $request->expires_at ? now()->parse($request->expires_at) : null
        );

        // Flash the plain text token to session (only shown once for security)
        return redirect()->route('admin.api-tokens.index')
            ->with('success', 'New API token generated successfully!')
            ->with('token', $token->plainTextToken)
            ->with('token_name', $request->token_name);
    }

    /**
     * Show the form for creating a new API token.
     */
    public function create()
    {
        // Get users who can have API tokens
        $roleNames = ['admin', 'staff', 'college-admin', 'super-admin', 'accountant'];
        $existingRoles = Role::whereIn('name', $roleNames)->pluck('name')->toArray();

        if (empty($existingRoles)) {
            $users = collect();
        } else {
            $users = User::role($existingRoles)->orderBy('name')->get();
        }

        // Define available abilities
        $availableAbilities = [
            '*' => 'All permissions',
            'read' => 'Read access only',
            'write' => 'Write access',
            'attendance' => 'Attendance management',
            'students' => 'Student management',
            'reports' => 'Generate reports',
        ];

        return view('admin.api_tokens.create', compact('users', 'availableAbilities'));
    }

    /**
     * Display the specified API token details.
     */
    public function show(PersonalAccessToken $token)
    {
        $token->load('tokenable');
        return view('admin.api_tokens.show', compact('token'));
    }

    /**
     * Show the form for editing the specified API token.
     */
    public function edit(PersonalAccessToken $token)
    {
        $token->load('tokenable');

        $availableAbilities = [
            '*' => 'All permissions',
            'read' => 'Read access only',
            'write' => 'Write access',
            'attendance' => 'Attendance management',
            'students' => 'Student management',
            'reports' => 'Generate reports',
        ];

        return view('admin.api_tokens.edit', compact('token', 'availableAbilities'));
    }

    /**
     * Update the specified API token.
     */
    public function update(Request $request, PersonalAccessToken $token)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:personal_access_tokens,name,' . $token->id,
            'abilities' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $token->update([
            'name' => $request->name,
            'abilities' => $request->abilities ?? ['*'],
            'expires_at' => $request->expires_at ? now()->parse($request->expires_at) : null,
        ]);

        return redirect()->route('admin.api-tokens.index')
            ->with('success', 'API token updated successfully.');
    }

    /**
     * Remove the specified API token from storage.
     */
    public function destroy($tokenId)
    {
        try {
            $token = PersonalAccessToken::findOrFail($tokenId);
            $tokenName = $token->name;
            $userName = $token->tokenable->name ?? 'Unknown User';

            $token->delete();

            return redirect()->route('admin.api-tokens.index')
                ->with('success', "API token '{$tokenName}' for {$userName} has been revoked successfully.");
        } catch (\Exception $e) {
            return redirect()->route('admin.api-tokens.index')
                ->with('error', 'Failed to revoke API token. Please try again.');
        }
    }

    /**
     * Revoke all tokens for a specific user.
     */
    public function revokeUserTokens(User $user)
    {
        $tokenCount = $user->tokens()->count();
        $user->tokens()->delete();

        return redirect()->route('admin.api-tokens.index')
            ->with('success', "All {$tokenCount} API tokens for {$user->name} have been revoked.");
    }

    /**
     * Export API tokens to CSV.
     */
    public function export()
    {
        $tokens = PersonalAccessToken::with('tokenable')->latest()->get();

        $filename = 'api-tokens-' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($tokens) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            // Header row
            fputcsv($file, ['ID', 'Token Name', 'User', 'User Email', 'Abilities', 'Last Used', 'Created At', 'Expires At', 'Status']);

            foreach ($tokens as $token) {
                // Determine status
                $isExpired = $token->expires_at && $token->expires_at->isPast();
                $status = $isExpired ? 'Expired' : 'Active';

                fputcsv($file, [
                    $token->id,
                    $token->name,
                    $token->tokenable->name ?? 'Unknown',
                    $token->tokenable->email ?? 'Unknown',
                    implode(', ', $token->abilities ?? []),
                    $token->last_used_at ? $token->last_used_at->format('Y-m-d H:i:s') : 'Never',
                    $token->created_at->format('Y-m-d H:i:s'),
                    $token->expires_at ? $token->expires_at->format('Y-m-d H:i:s') : 'Never',
                    $status
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Clean up expired tokens.
     */
    public function cleanupExpired()
    {
        $expiredCount = PersonalAccessToken::where('expires_at', '<', now())->count();
        PersonalAccessToken::where('expires_at', '<', now())->delete();

        return redirect()->route('admin.api-tokens.index')
            ->with('success', "Cleaned up {$expiredCount} expired API tokens.");
    }

    /**
     * Regenerate an existing token (creates new token and deletes old one).
     */
    public function regenerate(PersonalAccessToken $token)
    {
        $user = $token->tokenable;
        $name = $token->name;
        $abilities = $token->abilities;
        $expiresAt = $token->expires_at;

        // Delete the old token
        $token->delete();

        // Create a new token with the same properties
        $newToken = $user->createToken($name, $abilities, $expiresAt);

        return redirect()->route('admin.api-tokens.index')
            ->with('success', 'API token regenerated successfully!')
            ->with('token', $newToken->plainTextToken)
            ->with('token_name', $name);
    }

    /**
     * Bulk actions on tokens.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,revoke',
            'token_ids' => 'required|array',
            'token_ids.*' => 'exists:personal_access_tokens,id',
        ]);

        $tokens = PersonalAccessToken::whereIn('id', $request->token_ids);
        $count = $tokens->count();

        switch ($request->action) {
            case 'delete':
            case 'revoke':
                $tokens->delete();
                $message = "Successfully revoked {$count} API tokens.";
                break;
            default:
                $message = "Unknown action.";
        }

        return redirect()->route('admin.api-tokens.index')
            ->with('success', $message);
    }

    /**
     * Test an API token to see if it's working.
     */
    public function test(PersonalAccessToken $token)
    {
        $isValid = !$token->expires_at || $token->expires_at->isFuture();

        $testResults = [
            'token_name' => $token->name,
            'user' => $token->tokenable->name,
            'is_valid' => $isValid,
            'last_used' => $token->last_used_at,
            'expires_at' => $token->expires_at,
            'abilities' => $token->abilities,
        ];

        return response()->json($testResults);
    }
}