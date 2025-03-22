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

class RegistrationController extends Controller
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



}
