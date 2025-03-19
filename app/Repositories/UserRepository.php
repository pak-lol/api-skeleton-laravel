<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    public function all(): Collection
    {
        return User::all();
    }

    public function find(int $id): ?User
    {
        return User::find($id);
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
     * @param int $id
     * @return array|null
     */
    public function getPublicUserData(int $id): ?array
    {
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
        return User::orderBy($sortBy, $sortDir)->paginate($perPage);
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
