<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TokenService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
// cache
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    use ApiResponseTrait;

    protected $tokenService;

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
     * Register a new user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:3|max:14|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create both access and refresh tokens
        $tokens = $this->tokenService->createTokens($user, $request);

        return $this->successResponse([
            'user' => $user,
            'access_token' => $tokens['access_token'],
            'token_type' => $tokens['token_type'],
            'expires_in' => $tokens['expires_in'],
            'refresh_token' => $tokens['refresh_token'],
        ], 'User created successfully');
    }

    /**
     * Refresh a token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $tokens = $this->tokenService->refreshAccessToken($request->refresh_token, $request);

        if (!$tokens) {
            return $this->errorResponse('Invalid or expired refresh token', null, 401);
        }

        return $this->successResponse([
            'access_token' => $tokens['access_token'],
            'token_type' => $tokens['token_type'],
            'expires_in' => $tokens['expires_in'],
            'refresh_token' => $tokens['refresh_token'],
        ], 'Token refreshed successfully');
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            // Invalidate JWT token
            JWTAuth::invalidate(JWTAuth::getToken());

            // Revoke refresh token if provided
            if ($request->has('refresh_token')) {
                $this->tokenService->revokeToken($request->refresh_token);
            }

            return $this->successResponse(null, 'Successfully logged out');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to logout', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log the user out from all devices.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutAll()
    {
        try {
            // Invalidate current JWT token
            JWTAuth::invalidate(JWTAuth::getToken());

            // Revoke all refresh tokens for the user
            $this->tokenService->revokeAllTokens(auth()->user());

            return $this->successResponse(null, 'Successfully logged out from all devices');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to logout from all devices', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return $this->successResponse(auth()->user(), 'User profile retrieved successfully');
    }
}
