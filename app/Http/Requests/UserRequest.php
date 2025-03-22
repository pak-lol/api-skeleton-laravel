<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * List of supported application languages
     *
     * @var array
     */
    private $supportedLanguages = ['en', 'lt'];

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $userId = auth('api')->id();
        $method = $this->method();

        // Validation for updating user profile
        if ($this->routeIs('api.users.update')) {
            return $this->getUserUpdateRules($userId);
        }

        // Validation for search parameters
        if ($this->routeIs('api.users.index') || $this->routeIs('api.users.search')) {
            return $this->getSearchRules();
        }

        // Default empty rules
        return [];
    }

    /**
     * Get user update validation rules
     *
     * @param int $userId
     * @return array
     */
    protected function getUserUpdateRules(int $userId): array
    {
        return [
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            'password' => 'sometimes|min:8|confirmed',
            'username' => [
                'sometimes',
                'string',
                'min:3',
                'max:14',
                'unique:users,username,' . $userId,
                'regex:/^[a-zA-Z0-9]+$/'
            ],
            'locale' => 'sometimes|in:' . implode(',', $this->supportedLanguages),
            'name' => 'sometimes|string|max:255'
        ];
    }

    /**
     * Get search validation rules
     *
     * @return array
     */
    protected function getSearchRules(): array
    {
        return [
            'search' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|string|in:id,username,email,name,created_at,updated_at',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'role' => 'nullable|string',
            'status' => 'nullable|string',
            'locale' => 'nullable|string|in:' . implode(',', $this->supportedLanguages),
            'created_after' => 'nullable|date',
            'created_before' => 'nullable|date',
        ];
    }

    /**
     * Extract filters from validated data
     *
     * @return array
     */
    public function filters(): array
    {
        $filters = [];
        $validated = $this->validated();

        // Add role filter if present
        if (isset($validated['role'])) {
            $filters['role'] = $validated['role'];
        }

        // Add status filter if present
        if (isset($validated['status'])) {
            $filters['status'] = $validated['status'];
        }

        // Add locale filter if present
        if (isset($validated['locale'])) {
            $filters['locale'] = $validated['locale'];
        }

        // Add date range filters if present
        if (isset($validated['created_after'])) {
            $filters['created_after'] = $validated['created_after'];
        }

        if (isset($validated['created_before'])) {
            $filters['created_before'] = $validated['created_before'];
        }

        return $filters;
    }

    /**
     * Get search parameters with defaults
     *
     * @return array
     */
    public function searchParams(): array
    {
        $validated = $this->validated();

        return [
            'searchTerm' => $validated['search'] ?? null,
            'perPage' => (int) ($validated['per_page'] ?? 15),
            'sortBy' => $validated['sort_by'] ?? 'id',
            'sortDir' => $validated['sort_dir'] ?? 'asc',
        ];
    }
}
