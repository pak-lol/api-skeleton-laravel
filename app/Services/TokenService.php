<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class TokenService
{
    /**
     * Create a new access token and refresh token for a user.
     *
     * @param User $user
     * @param Request $request
     * @return array
     */
    public function createTokens(User $user, Request $request)
    {
        // Generate JWT access token
        $accessToken = JWTAuth::fromUser($user);

        // Generate refresh token
        $refreshToken = $this->createRefreshToken($user, $request);

        return [
            'access_token' => $accessToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'refresh_token' => $refreshToken->token,
        ];
    }

    /**
     * Create a refresh token for a user.
     *
     * @param User $user
     * @param Request $request
     * @return RefreshToken
     */
    public function createRefreshToken(User $user, Request $request)
    {
        // Generate a unique token
        $token = Str::random(80);

        // Set expiration (default: 30 days)
        $expires = now()->addDays(config('auth.refresh_token_ttl', 30));

        return RefreshToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => $expires,
            'device' => $request->header('Device', null),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Refresh an access token using a refresh token.
     *
     * @param string $refreshToken
     * @param Request $request
     * @return array|null
     */
    public function refreshAccessToken(string $refreshToken, Request $request)
    {
        // Find and validate the refresh token
        $tokenRecord = RefreshToken::where('token', $refreshToken)
            ->valid()
            ->first();

        if (!$tokenRecord) {
            return null;
        }

        // Get the user
        $user = $tokenRecord->user;

        // Revoke the old refresh token for security
        $tokenRecord->revoke();

        // Generate new tokens
        return $this->createTokens($user, $request);
    }

    /**
     * Revoke all refresh tokens for a user.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|User $user
     * @return int Number of tokens revoked
     */
    public function revokeAllTokens($user)
    {
        // Get the user ID from any authenticatable object
        $userId = $user->getAuthIdentifier();

        return RefreshToken::where('user_id', $userId)
            ->update(['revoked' => true]);
    }

    /**
     * Revoke refresh tokens by token string.
     *
     * @param string $token
     * @return bool
     */
    public function revokeToken(string $token)
    {
        $tokenRecord = RefreshToken::where('token', $token)->first();

        if ($tokenRecord) {
            return $tokenRecord->revoke();
        }

        return false;
    }

    /**
     * Clean up expired tokens (can be run via scheduler).
     *
     * @return int Number of tokens deleted
     */
    public function purgeExpiredTokens()
    {
        return RefreshToken::where('expires_at', '<', now())
            ->delete();
    }
}
