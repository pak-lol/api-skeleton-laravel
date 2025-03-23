<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegistrationController extends Controller
{
    use ApiResponseTrait;

    /**
     * The token service instance.
     *
     * @var TokenService
     */
    protected $tokenService;

    /**
     * Create a new controller instance.
     *
     * @param TokenService $tokenService
     * @return void
     */
    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
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
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        // Create user
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user', // Default role
        ]);

        // Get device name or use a default
        $deviceName = $request->device_name ?? $request->userAgent() ?? 'registration';

        // Generate tokens
        $tokenResponse = $this->tokenService->createTokens($user, $deviceName);

        // Return user with tokens
        return $this->successResponse(
            array_merge(['user' => $user], $tokenResponse),
            'User registered successfully'
        );
    }
}
