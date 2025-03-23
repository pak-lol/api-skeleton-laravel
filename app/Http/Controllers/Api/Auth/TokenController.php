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

class TokenController extends Controller
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
     * Refresh access token using a refresh token
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

        try {
            // Set flag to revoke previous tokens
            $revokeOldTokens = true;

            // Get response with new tokens and revoked old ones
            $response = $this->tokenService->refreshToken($request->refresh_token, $revokeOldTokens);
            return $this->successResponse($response, 'Token refreshed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 401);
        }
    }
}
