<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TokenService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends Controller
{
    use ApiResponseTrait;

    protected $tokenService;

    /**
     * Maximum number of failed attempts before lockout
     */
    protected const MAX_ATTEMPTS = 5;

    /**
     * Lockout duration in minutes
     */
    protected const LOCKOUT_TIME = 5;

    /**
     * Create a new AuthController instance.
     *
     * @param TokenService $tokenService
     */
    public function __construct(TokenService $tokenService = null)
    {
        $this->tokenService = $tokenService ?? app(TokenService::class);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Get the request IP address for rate limiting
        $ipAddress = $request->ip();

        // Check if the IP is currently locked out
        if ($this->isIpLocked($ipAddress)) {
            $timeRemaining = $this->getTimeRemainingForLockout($ipAddress);
            return $this->errorResponse(
                __('messages.too_many_attempts', ['minutes' => ceil($timeRemaining / 60)]),
                null,
                429
            );
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            // Increment failed attempts for validation errors related to invalid email format
            if (isset($validator->errors()->toArray()['email'])) {
                $this->incrementFailedAttempts($ipAddress);
            }
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $credentials = $request->only('email', 'password');

        if (! $token = JWTAuth::attempt($credentials)) {
            // Increment failed attempts for invalid credentials
            $this->incrementFailedAttempts($ipAddress);

            return $this->errorResponse(
                __('messages.auth_failed'),
                ['attempts_remaining' => $this->getRemainingAttempts($ipAddress)],
                401
            );
        }

        // Reset failed attempts on successful login
        $this->resetFailedAttempts($ipAddress);

        // Get user as an App\Models\User instance
        $user = User::find(auth()->id());

        // revoke all existing refresh tokens
        $this->tokenService->revokeAllTokens($user);

        // Create both access and refresh tokens
        $tokens = $this->tokenService->createTokens($user, $request);

        return $this->successResponse([
            'user' => $user,
            'access_token' => $tokens['access_token'],
            'token_type' => $tokens['token_type'],
            'expires_in' => $tokens['expires_in'],
            'refresh_token' => $tokens['refresh_token'],
        ], 'Login successful');
    }

    /**
     * Check if the IP address is currently locked out
     *
     * @param string $ipAddress
     * @return bool
     */
    protected function isIpLocked($ipAddress)
    {
        return RateLimiter::tooManyAttempts($this->getLimiterKey($ipAddress), self::MAX_ATTEMPTS);
    }

    /**
     * Get the rate limiter key for the given IP address
     *
     * @param string $ipAddress
     * @return string
     */
    protected function getLimiterKey($ipAddress)
    {
        return 'login:' . $ipAddress;
    }

    /**
     * Increment the failed login attempts for the IP address
     *
     * @param string $ipAddress
     * @return void
     */
    protected function incrementFailedAttempts($ipAddress)
    {
        RateLimiter::hit($this->getLimiterKey($ipAddress), 60 * self::LOCKOUT_TIME);
    }

    /**
     * Reset the failed login attempts for the IP address
     *
     * @param string $ipAddress
     * @return void
     */
    protected function resetFailedAttempts($ipAddress)
    {
        RateLimiter::clear($this->getLimiterKey($ipAddress));
    }

    /**
     * Get the remaining attempts allowed before lockout
     *
     * @param string $ipAddress
     * @return int
     */
    protected function getRemainingAttempts($ipAddress)
    {
        return RateLimiter::remaining($this->getLimiterKey($ipAddress), self::MAX_ATTEMPTS);
    }

    /**
     * Get the time remaining (in seconds) for the lockout to expire
     *
     * @param string $ipAddress
     * @return int
     */
    protected function getTimeRemainingForLockout($ipAddress)
    {
        return RateLimiter::availableIn($this->getLimiterKey($ipAddress));
    }
}
