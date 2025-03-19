<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use ApiResponseTrait;
    private $userRepository;
    private $supportedLanguages = ['en', 'lt'];

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function index()
    {
        $users = $this->userRepository->all();
        return $this->successResponse($users);
    }

    public function show($id)
    {
        $userData = $this->userRepository->getPublicUserData($id);

        if (!$userData) {
            return $this->errorResponse(__('messages.user_not_found'), null, 404);
        }

        return $this->successResponse($userData);
    }

    // change language /en or /lt in language table
    public function updateLanguage(Request $request, $locale)
    {
        // Validate the language
        if (!in_array($locale, $this->supportedLanguages)) {
            return $this->errorResponse(__('messages.unsupported_language'));
        }

        try {
            // For JWT, use the auth guard explicitly
            $user = auth('api')->user();

            if (!$user) {
                return $this->errorResponse(__('messages.unauthorized'), null, 401);
            }

            // Update the locale
            $user->locale = $locale;
            $user->save();

            // Also set the app locale for the current request
            app()->setLocale($locale);

            return $this->successResponse([
                'locale' => $locale,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    public function me(){
        $user = auth('api')->user();
        return $this->successResponse(compact('user'));
    }

    /**
     * Update user information
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            // Get authenticated user
            $user = auth('api')->user();

            if (!$user) {
                return $this->errorResponse(__('messages.unauthorized'), null, 401);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'password' => 'sometimes|min:8|confirmed',
                'username' => 'sometimes|string|min:3|max:14|unique:users,username,' . $user->id . '|regex:/^[a-zA-Z0-9]+$/',
                'locale' => 'sometimes|in:' . implode(',', $this->supportedLanguages),
                // Add any other fields you want to allow updating
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(__('messages.validation_error'), $validator->errors(), 422);
            }

            // Update user
            $result = $this->userRepository->updateUser($user->id, $request->all());

            if (!$result) {
                return $this->errorResponse(__('messages.update_failed'), null, 500);
            }

            // Get fresh user data
            $updatedUser = $this->userRepository->find($user->id);

            return $this->successResponse([
                'message' => __('messages.user_updated_successfully'),
                'user' => $updatedUser
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }
}
