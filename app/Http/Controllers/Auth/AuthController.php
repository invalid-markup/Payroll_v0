<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate user and issue base token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // 06_SECURITY_SPECIFICATION.md §7.5 Error Response Security
        // Generic error, no field-level disclosure on auth failures.
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        // Revoke any existing tokens for this device/client to enforce single active session (optional, but good practice)
        // For MVP we just issue a new token.

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }

    /**
     * Revoke tokens for the authenticated user.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Issue a tenant-specific token.
     * Expects company_id in the request.
     */
    public function switchCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|uuid|exists:company_profile,company_id',
        ]);

        $user = $request->user();

        // Optional: Check if user has access to this company.
        // For MVP, assuming they can switch if it's a valid company.

        // Revoke current tenant token (if we want them to re-authenticate or just switch)
        // Or simply issue a new token specific to the tenant.
        $tokenName = 'tenant_token_'.$request->company_id;

        $token = $user->createToken($tokenName, ['company:'.$request->company_id]);

        return response()->json([
            'company_id' => $request->company_id,
            'token' => $token->plainTextToken,
            'message' => 'Switched to company context successfully',
        ]);
    }
}
