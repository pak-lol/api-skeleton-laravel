<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LanguageRequest;
use App\Http\Requests\UserRequest;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * UserController constructor
     *
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Get list of all users with optional search parameters
     *
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function index(UserRequest $request): JsonResponse
    {
        try {
            // Get search parameters and filters
            $searchParams = $request->searchParams();
            $filters = $request->filters();

            // Call repository search method
            $users = $this->userRepository->searchUsers(
                $searchParams['searchTerm'],
                $filters,
                $searchParams['perPage'],
                $searchParams['sortBy'],
                $searchParams['sortDir']
            );

            return $this->respondWith($users);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to fetch users: ' . $e->getMessage());
            return $this->errorResponse(__('messages.server_error'), null, 500);
        }
    }

    /**
     * Search users with advanced filtering
     *
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function search(UserRequest $request): JsonResponse
    {
        try {
            // Get search parameters and filters
            $searchParams = $request->searchParams();
            $filters = $request->filters();

            // Call repository search method
            $users = $this->userRepository->searchUsers(
                $searchParams['searchTerm'],
                $filters,
                $searchParams['perPage'],
                $searchParams['sortBy'],
                $searchParams['sortDir']
            );

            return $this->respondWith(
                $users,
                __('messages.users_found', ['count' => $users->total()])
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to search users: ' . $e->getMessage());
            return $this->errorResponse(__('messages.server_error'), null, 500);
        }
    }

    /**
     * Get public user data by ID
     *
     * @param mixed $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $userData = $this->userRepository->getPublicUserData($id);

            if (!$userData) {
                return $this->errorResponse(__('messages.user_not_found'), null, 404);
            }

            return $this->successResponse($userData);
        } catch (\Exception $e) {
            Log::error('Failed to fetch user data: ' . $e->getMessage());
            return $this->errorResponse(__('messages.server_error'), null, 500);
        }
    }

    /**
     * Update user language preference
     *
     * @param LanguageRequest $request
     * @return JsonResponse
     */
    public function updateLanguage(LanguageRequest $request): JsonResponse
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return $this->errorResponse(__('messages.unauthorized'), null, 401);
            }

            $locale = $request->validated()['locale'];

            // Update the locale
            $user->locale = $locale;
            $user->save();

            // Set the app locale for the current request
            app()->setLocale($locale);

            return $this->successResponse([
                'locale' => $locale,
                'message' => __('messages.language_updated_successfully')
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user language: ' . $e->getMessage());
            return $this->errorResponse(__('messages.server_error'), null, 500);
        }
    }

    /**
     * Get current authenticated user's data
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->errorResponse(__('messages.unauthorized'), null, 401);
            }

            return $this->successResponse(['user' => $user]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch current user data: ' . $e->getMessage());
            return $this->errorResponse(__('messages.server_error'), null, 500);
        }
    }

    /**
     * Update authenticated user's information
     *
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function update(UserRequest $request): JsonResponse
    {
        try {
            $userId = auth('api')->id();
            $validatedData = $request->validated();

            // Update user
            $result = $this->userRepository->updateUser($userId, $validatedData);

            if (!$result) {
                return $this->errorResponse(__('messages.update_failed'), null, 500);
            }

            // Get fresh user data
            $updatedUser = $this->userRepository->find($userId);

            return $this->successResponse([
                'message' => __('messages.user_updated_successfully'),
                'user' => $updatedUser
            ]);
        } catch (ValidationException $e) {
            return $this->errorResponse(
                __('messages.validation_error'),
                $e->errors(),
                422
            );
        } catch (\Exception $e) {
            Log::error('Failed to update user: ' . $e->getMessage());
            return $this->errorResponse(__('messages.server_error'), null, 500);
        }
    }

}
