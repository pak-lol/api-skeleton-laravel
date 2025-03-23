<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Services\TokenService;
use App\Services\RateLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    use ApiResponseTrait;

    /**
     * The token service instance.
     *
     * @var TokenService
     */
    protected $tokenService;

    /**
     * The rate limit service instance.
     *
     * @var RateLimitService
     */
    protected $rateLimitService;

    /**
     * Create a new controller instance.
     *
     * @param TokenService $tokenService
     * @param RateLimitService $rateLimitService
     * @return void
     */
    public function __construct(TokenService $tokenService, RateLimitService $rateLimitService)
    {
        $this->tokenService = $tokenService;
        $this->rateLimitService = $rateLimitService;
    }

    /**
     * Get a token via given credentials.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Get the request IP address for rate limiting
        $ipAddress = $request->ip();

        // Check if the IP is currently locked out
        if ($this->rateLimitService->isIpLocked($ipAddress)) {
            $timeRemaining = $this->rateLimitService->getTimeRemainingForLockout($ipAddress);
            return $this->errorResponse(
                __('messages.too_many_attempts', ['minutes' => ceil($timeRemaining / 60)]),
                null,
                429
            );
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            // Increment failed attempts for validation errors related to invalid email format
            if (isset($validator->errors()->toArray()['email'])) {
                $this->rateLimitService->incrementFailedAttempts($ipAddress);
            }
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        // Find the user by email
        $user = User::where('email', $request->email)->first();

        // Check if user exists and credentials are correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            // Increment failed attempts for invalid credentials
            $this->rateLimitService->incrementFailedAttempts($ipAddress);

            return $this->errorResponse(
                __('messages.auth_failed'),
                ['attempts_remaining' => $this->rateLimitService->getRemainingAttempts($ipAddress)],
                401
            );
        }

        // Reset failed attempts on successful login
        $this->rateLimitService->resetFailedAttempts($ipAddress);

        // Get device name or use a default
        $deviceName = $request->device_name ?? $request->userAgent() ?? 'unknown-device';

        // delete all previous tokens
        $user->tokens()->delete();

        // delete all previous refresh tokens
        $user->refreshTokens()->delete();

        // Generate tokens
        $tokenResponse = $this->tokenService->createTokens($user, $deviceName);

        return $this->successResponse(
            array_merge(['user' => $user], $tokenResponse),
            'Login successful'
        );
    }
}
