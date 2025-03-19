<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    use ApiResponseTrait;

    protected $userRepository;

    /**
     * Create a new AdminController instance.
     *
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository = null)
    {
        $this->userRepository = $userRepository ?? app(UserRepository::class);
    }

    /**
     * List users with pagination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:100',
            'sort_by' => 'nullable|string|in:id,username,email,created_at,updated_at',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        // Get pagination parameters
        $perPage = $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');

        // Get paginated users
        $users = $this->userRepository->paginate($perPage, $sortBy, $sortDir);

        // Return paginated response
        return $this->respondWith(
            $users,
            __('messages.users_listed'),
            [
                'filters' => [
                    'sort_by' => $sortBy,
                    'sort_dir' => $sortDir
                ]
            ]
        );
    }

    /**
     * Delete user
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->errorResponse(__('messages.user_not_found'), null, 404);
        }

        $this->userRepository->delete($id);

        return $this->successResponse(__('messages.user_deleted'));
    }

    /**
     * Get users who are currently online (active within last 10 minutes)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function online_list()
    {
        // Get users who were active in the last 10 minutes
        $onlineUsers = $this->userRepository->getRecentlyActiveUsers();

        // Map to include only necessary information
        $userData = $onlineUsers->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'last_active' => $user->updated_at->diffForHumans(),
                'last_active_at' => $user->updated_at,
            ];
        });

        return $this->successResponse([
            'online_count' => $onlineUsers->count(),
            'users' => $userData
        ]);
    }

    public function show($id)
    {
        // Ensure $id is not null and is numeric
        if ($id === null || !is_numeric($id)) {
            return $this->notFoundResponse('User ID is invalid or missing');
        }

        $user = $this->userRepository->find((int)$id);

        if (!$user) {
            return $this->notFoundResponse('User not found');
        }

        return $this->successResponse($user);
    }

    // de
}
