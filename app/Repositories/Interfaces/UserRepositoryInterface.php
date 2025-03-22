<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /**
     * Get all users
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Find user by ID
     *
     * @param mixed $id
     * @return User|null
     */
    public function find($id): ?User;

    /**
     * Create a new user
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User;

    /**
     * Update a user
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a user
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Get public user data
     *
     * @param mixed $id
     * @return array|null
     */
    public function getPublicUserData($id): ?array;

    /**
     * Get recently active users
     *
     * @param int $minutes
     * @return Collection
     */
    public function getRecentlyActiveUsers(int $minutes = 10): Collection;

    /**
     * Get paginated users
     *
     * @param int $perPage
     * @param string $sortBy
     * @param string $sortDir
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, string $sortBy = 'id', string $sortDir = 'asc'): LengthAwarePaginator;

    /**
     * Search users with pagination and filtering
     *
     * @param string|null $searchTerm
     * @param array $filters
     * @param int $perPage
     * @param string $sortBy
     * @param string $sortDir
     * @return LengthAwarePaginator
     */
    public function searchUsers(?string $searchTerm = null, array $filters = [], int $perPage = 15, string $sortBy = 'id', string $sortDir = 'asc'): LengthAwarePaginator;

    /**
     * Update user information
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateUser(int $id, array $data): bool;
}
