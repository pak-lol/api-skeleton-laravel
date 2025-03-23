<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TokenService
{
    /**
     * Token expiration time in minutes
     */
    protected const TOKEN_EXPIRATION = 60 * 24;

    /**
     * Refresh token expiration time in days
     */
    protected const REFRESH_TOKEN_EXPIRATION = 7; // 7 days

    /**
     * Maximum length for device name
     */
    protected const MAX_DEVICE_NAME_LENGTH = 100;

    /**
     * Create access and refresh tokens for a user
     *
     * @param User $user
     * @param string $deviceName
     * @return array
     */
    public function createTokens(User $user, string $deviceName): array
    {
        // Determine abilities based on user role
        $abilities = ['user'];
        if ($user->role === 'admin') {
            $abilities[] = 'admin';
        }

        // Process the device name to ensure it's not too long
        $sanitizedDeviceName = $this->sanitizeDeviceName($deviceName);

        // Create access token with expiration
        $expiresAt = now()->addMinutes(self::TOKEN_EXPIRATION);
        $plainTextToken = $this->createTokenWithExpiration($user, $sanitizedDeviceName, $abilities, $expiresAt);

        // Create refresh token
        $refreshToken = $this->createRefreshToken($user, $sanitizedDeviceName);

        return [
            'access_token' => $plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toDateTimeString(),
            'expires_in_seconds' => self::TOKEN_EXPIRATION * 60,
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * Sanitize device name to ensure it's not too long and extracts meaningful information
     *
     * @param string $deviceName
     * @return string
     */
    protected function sanitizeDeviceName(string $deviceName): string
    {
        // Check if it's an HTTP request string
        if (str_starts_with($deviceName, 'POST ') || str_starts_with($deviceName, 'GET ')) {
            // Extract just the HTTP method and path
            $parts = explode(' ', $deviceName);
            if (count($parts) >= 2) {
                $method = $parts[0];
                $path = $parts[1];

                // Extract browser from User-Agent if present
                $browser = 'Unknown Browser';
                if (preg_match('/User-Agent: .*?(Chrome|Firefox|Safari|Edge|Opera)[\/\s]([0-9.]+)/i', $deviceName, $matches)) {
                    $browser = $matches[1] . ' ' . $matches[2];
                }

                return substr("$method $path ($browser)", 0, self::MAX_DEVICE_NAME_LENGTH);
            }
        }

        // Default fallback - just truncate
        return substr($deviceName, 0, self::MAX_DEVICE_NAME_LENGTH);
    }

    /**
     * Create a new token with expiration for the user.
     *
     * @param User $user
     * @param string $deviceName
     * @param array $abilities
     * @param \DateTime $expiresAt
     * @return string
     */
    protected function createTokenWithExpiration($user, $deviceName, array $abilities, $expiresAt)
    {
        // Revoke existing tokens with the same device name first
        $user->tokens()->where('name', $deviceName)->delete();

        // Create new token
        $token = $user->tokens()->create([
            'name' => $deviceName,
            'token' => hash('sha256', $plainTextToken = Str::random(40)),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return $plainTextToken;
    }

    /**
     * Create a refresh token for the user
     *
     * @param User $user
     * @param string $deviceName
     * @return string
     */
    protected function createRefreshToken($user, $deviceName)
    {
        $plainTextToken = Str::random(60);

        // Remove any existing refresh tokens for this device
        DB::table('refresh_tokens')
            ->where('user_id', $user->id)
            ->where('device', $deviceName)
            ->delete();

        // Create new refresh token
        DB::table('refresh_tokens')->insert([
            'user_id' => $user->id,
            'device' => $deviceName,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(self::REFRESH_TOKEN_EXPIRATION),
            'created_at' => now(),
            'updated_at' => now(),
            'revoked' => false,
        ]);

        return $plainTextToken;
    }

    /**
     * Refresh an access token using a refresh token
     *
     * @param string $refreshToken
     * @param bool $revokeOldTokens Whether to revoke all previous access tokens
     * @return array
     * @throws \Exception
     */
    public function refreshToken(string $refreshToken, bool $revokeOldTokens = true): array
    {
        // Find the refresh token
        $refreshTokenRecord = DB::table('refresh_tokens')
            ->where('token', hash('sha256', $refreshToken))
            ->first();

        if (!$refreshTokenRecord) {
            throw new \Exception('Invalid refresh token');
        }

        // Check if refresh token is already used (revoked)
        if ($refreshTokenRecord->revoked) {
            throw new \Exception('Refresh token has already been used');
        }

        // Check if refresh token is expired
        if (now()->gt($refreshTokenRecord->expires_at)) {
            // Delete expired refresh token
            DB::table('refresh_tokens')
                ->where('id', $refreshTokenRecord->id)
                ->delete();

            throw new \Exception('Refresh token expired');
        }

        // Get user
        $user = User::findOrFail($refreshTokenRecord->user_id);

        // If revokeOldTokens is true, revoke all previous access tokens
        if ($revokeOldTokens) {
            // Revoke all previous access tokens
            $user->tokens()->delete();
        }

        // Mark this refresh token as used (revoked)
        DB::table('refresh_tokens')
            ->where('id', $refreshTokenRecord->id)
            ->update(['revoked' => true]);

        // Determine abilities based on user role
        $abilities = ['user'];
        if ($user->role === 'admin') {
            $abilities[] = 'admin';
        }

        // Create new access token
        $expiresAt = now()->addMinutes(self::TOKEN_EXPIRATION);
        $plainTextToken = $this->createTokenWithExpiration(
            $user,
            $refreshTokenRecord->device,
            $abilities,
            $expiresAt
        );

        // Create a new refresh token to replace the used one
        $newRefreshToken = Str::random(60);

        DB::table('refresh_tokens')->insert([
            'user_id' => $user->id,
            'device' => $refreshTokenRecord->device,
            'token' => hash('sha256', $newRefreshToken),
            'expires_at' => now()->addDays(self::REFRESH_TOKEN_EXPIRATION),
            'created_at' => now(),
            'updated_at' => now(),
            'revoked' => false,
        ]);

        return [
            'access_token' => $plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toDateTimeString(),
            'expires_in_seconds' => self::TOKEN_EXPIRATION * 60,
            'refresh_token' => $newRefreshToken,
        ];
    }

    /**
     * Revoke all tokens for a user on the current device
     *
     * @param User $user
     * @return void
     */
    public function revokeTokens(User $user): void
    {
        // Get the device name from the current token
        $deviceName = $user->currentAccessToken()->name;

        // Revoke the access token
        $user->currentAccessToken()->delete();

        // Revoke any refresh tokens for this device
        DB::table('refresh_tokens')
            ->where('user_id', $user->id)
            ->where('device', $deviceName)
            ->update(['revoked' => true]);
    }

    /**
     * Revoke all tokens for a user across all devices
     *
     * @param User $user
     * @return void
     */
    public function revokeAllTokens(User $user): void
    {
        // Revoke all access tokens
        $user->tokens()->delete();

        // Revoke all refresh tokens
        DB::table('refresh_tokens')
            ->where('user_id', $user->id)
            ->update(['revoked' => true]);
    }
}
