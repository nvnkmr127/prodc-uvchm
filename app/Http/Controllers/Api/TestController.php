<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * Test API endpoint to verify authentication
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        return response()->json([
            'success' => true,
            'message' => 'API authentication successful!',
            'data' => [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_roles' => $user->getRoleNames(),
                'token_name' => $token->name ?? 'Unknown',
                'token_abilities' => $token->abilities ?? ['*'],
                'last_used_at' => $token->last_used_at,
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get user profile information
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }
}
