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
}
