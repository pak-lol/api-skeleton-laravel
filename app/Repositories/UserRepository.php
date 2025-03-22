<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection as SupportCollection;

class UserRepository implements UserRepositoryInterface
{
    public function all(): Collection
    {
        return User::all();
    }

    public function find($id): ?User
    {
        // Ensure we have a valid integer ID
        if ($id === null || !is_numeric($id)) {
            return null;
        }

        return User::find((int)$id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $user = $this->find($id);
        if (!$user) {
            return false;
        }
        return $user->update($data);
    }

    public function delete(int $id): bool
    {
        $user = $this->find($id);
        if (!$user) {
            return false;
        }
        return $user->delete();
    }

    /**
     * Get public user data (without sensitive information)
     *
     * @param int|string $id
     * @return array|null
     */
    public function getPublicUserData($id): ?array
    {
        // Convert string to integer if it's numeric
        if (is_string($id) && is_numeric($id)) {
            $id = (int)$id;
        }

        // If after conversion it's not an integer, return null
        if (!is_int($id)) {
            return null;
        }

        $user = $this->find($id);

        if (!$user) {
            return null;
        }

        // Return only non-sensitive fields
        return [
            'id' => $user->id,
            'username' => $user->username,
            'created_at' => $user->created_at,
            // Add any other public fields you want to include
        ];
    }

    /**
     * Get users who were online recently (within the specified minutes)
     *
     * @param int $minutes
     * @return Collection
     */
    public function getRecentlyActiveUsers(int $minutes = 10): Collection
    {
        return User::where('updated_at', '>=', now()->subMinutes($minutes))
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get paginated list of users with sorting options
     *
     * @param int $perPage
     * @param string $sortBy
     * @param string $sortDir
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, string $sortBy = 'id', string $sortDir = 'asc'): LengthAwarePaginator
    {
        $users = User::orderBy($sortBy, $sortDir)->paginate($perPage);

        // Transform the items to include only public data
        $publicItems = $this->transformUsersToPublic($users->items());

        // Create a new paginator with the transformed items
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $publicItems,
            $users->total(),
            $users->perPage(),
            $users->currentPage(),
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Search users with pagination, sorting and filtering
     * Returns only public data for each user
     *
     * @param string|null $searchTerm  Search term to filter users
     * @param array $filters           Additional filters (e.g. ['role' => 'admin'])
     * @param int $perPage             Number of results per page
     * @param string $sortBy           Field to sort by
     * @param string $sortDir          Sort direction (asc or desc)
     * @return LengthAwarePaginator
     */
    public function searchUsers(?string $searchTerm = null, array $filters = [], int $perPage = 15, string $sortBy = 'id', string $sortDir = 'asc'): LengthAwarePaginator
    {
        try {
            $query = User::query();

            // Apply search term if provided
            if ($searchTerm) {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('username', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('name', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Apply additional filters
            foreach ($filters as $field => $value) {
                if ($value === null) continue;

                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }

            // Apply sorting
            $query->orderBy($sortBy, $sortDir);

            // Get paginated results
            $users = $query->paginate($perPage);

            // Transform the items to include only public data
            $publicItems = $this->transformUsersToPublic($users->items());

            // Create a new paginator with the transformed items
            return new \Illuminate\Pagination\LengthAwarePaginator(
                $publicItems,
                $users->total(),
                $users->perPage(),
                $users->currentPage(),
                [
                    'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                    'pageName' => 'page',
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error searching users: ' . $e->getMessage());
            // Return empty paginator
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, 1);
        }
    }

    /**
     * Transform a collection of user models to only include public data
     *
     * @param array $users
     * @return array
     */
    private function transformUsersToPublic(array $users): array
    {
        $publicUsers = [];

        foreach ($users as $user) {
            $publicUsers[] = $this->getPublicUserData($user->id);
        }

        return $publicUsers;
    }

    /**
     * Update user information
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateUser(int $id, array $data): bool
    {
        try {
            $user = $this->find($id);

            if (!$user) {
                return false;
            }

            // Update email if provided
            if (isset($data['email'])) {
                $user->email = $data['email'];
            }

            // Update password if provided
            if (isset($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            // Update name if provided
            if (isset($data['name'])) {
                $user->name = $data['name'];
            }

            // Update username if provided
            if (isset($data['username'])) {
                $user->username = $data['username'];
            }

            // Update locale if provided
            if (isset($data['locale'])) {
                $user->locale = $data['locale'];
            }

            // Add any other fields you want to support
            // If there are many fields, consider using $user->fill($data) with $fillable in the model

            return $user->save();
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user's email and password (deprecated, use updateUser instead)
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @deprecated
     */
    public function updateEmailAndPassword(int $id, array $data): bool
    {
        return $this->updateUser($id, $data);
    }
}
