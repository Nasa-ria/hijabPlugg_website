<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * POST /api/admin/users — create an admin account from a script or API client.
 *
 * Guarded by the ADMIN_API_TOKEN secret, sent as "Authorization: Bearer <token>".
 * When ADMIN_API_TOKEN is not set the endpoint answers 404, as if it didn't exist.
 */
class AdminUserController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $secret = (string) config('app.admin_api_token');

        abort_if($secret === '', 404);

        if (! hash_equals($secret, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            'is_super_admin' => ['sometimes', 'boolean'],
            'admin_permissions' => ['sometimes', 'array'],
            'admin_permissions.*' => ['string', 'in:' . implode(',', array_keys(User::ADMIN_SECTIONS))],
        ]);

        $isSuper = (bool) ($data['is_super_admin'] ?? false);

        // Same rule as the Team page: a regular admin needs at least one page
        if (! $isSuper && empty($data['admin_permissions'])) {
            return response()->json([
                'message' => 'Choose at least one page this admin can use.',
                'errors' => ['admin_permissions' => ['Required unless is_super_admin is true.']],
            ], 422);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'], // hashed by the model's 'hashed' cast
            'role' => 'admin',
            'is_active' => true,
            'is_super_admin' => $isSuper,
            'admin_permissions' => $isSuper ? null : array_values($data['admin_permissions']),
            'must_change_password' => false,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        return response()->json([
            'message' => 'Admin created.',
            'user' => $user->only(['id', 'name', 'email', 'role', 'is_super_admin', 'admin_permissions']),
        ], 201);
    }

    
}
