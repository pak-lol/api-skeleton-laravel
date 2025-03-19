<?php

namespace App\Traits;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

trait ApiResponseTrait
{
    /**
     * Send a success response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data = null, string $message = null, int $statusCode = 200)
    {
        if (is_null($message)) {
            $message = __('messages.success');
        }
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'status_code' => $statusCode,
            'timestamp' => now()->toIso8601String(),
        ], $statusCode);
    }

    /**
     * Send an error response.
     *
     * @param string $message
     * @param mixed $errors
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $message = 'An error occurred', $errors = null, int $statusCode = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'status_code' => $statusCode,
            'timestamp' => now()->toIso8601String(),
        ], $statusCode);
    }

    /**
     * Send a validation error response.
     *
     * @param array $errors
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationErrorResponse(array $errors, string $message = null)
    {
        $message = $message ?? __('messages.validation_failed');
        return $this->errorResponse($message, $errors, 422);
    }

    /**
     * Send a not found response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFoundResponse(string $message = null)
    {
        $message = $message ?? __('messages.resource_not_found');
        return $this->errorResponse($message, null, 404);
    }

    /**
     * Send an unauthorized response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorizedResponse(string $message = null)
    {
        $message = $message ?? __('messages.unauthorized');
        return $this->errorResponse($message, null, 401);
    }

    /**
     * Send a forbidden response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Forbidden access')
    {
        return $this->errorResponse($message, null, 403);
    }

    /**
     * Send a server error response.
     *
     * @param string $message
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function serverErrorResponse(string $message = 'Server error', $errors = null)
    {
        return $this->errorResponse($message, $errors, 500);
    }

    /**
     * Send a created response.
     *
     * @param mixed $data
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createdResponse($data = null, string $message = 'Resource created successfully')
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Send a no content response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function noContentResponse()
    {
        return response()->json(null, 204);
    }

    /**
     * Send a response for any type of data (collection, paginator, or single resource)
     * with appropriate handling for each type.
     *
     * @param mixed $data The data to be returned (Collection, Paginator, Resource, or array)
     * @param string|null $message Optional message
     * @param array $meta Additional metadata
     * @param int $statusCode HTTP status code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWith($data, string $message = null, array $meta = [], int $statusCode = 200)
    {
        // Default response structure
        $response = [
            'success' => true,
            'message' => $message ?? __('messages.success'),
            'status_code' => $statusCode,
            'timestamp' => now()->toIso8601String(),
        ];

        // Handle different data types appropriately
        if ($data instanceof AbstractPaginator) {
            // Handle pagination
            $response['data'] = $data->items();
            $response['meta'] = array_merge($meta, [
                'pagination' => [
                    'total' => $data->total(),
                    'per_page' => $data->perPage(),
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                ],
            ]);

            // Add links section according to JSON:API spec
            $response['links'] = [
                'first' => $data->url(1),
                'last' => $data->url($data->lastPage()),
                'prev' => $data->previousPageUrl(),
                'next' => $data->nextPageUrl(),
                'self' => $data->url($data->currentPage()),
            ];
        } elseif ($data instanceof ResourceCollection) {
            // Handle Laravel API resource collections
            $resourceResponse = $data->response()->getData(true);
            $response['data'] = $resourceResponse['data'] ?? [];

            // If resource has pagination metadata
            if (isset($resourceResponse['meta']) && isset($resourceResponse['meta']['links'])) {
                $response['meta'] = array_merge($meta, $resourceResponse['meta']);
                $response['links'] = $resourceResponse['links'] ?? [];
            } else {
                $response['meta'] = array_merge($meta, [
                    'count' => count($resourceResponse['data'])
                ]);
            }
        } elseif ($data instanceof Collection) {
            // Handle regular Laravel collections
            $response['data'] = $data->values()->all();
            $response['meta'] = array_merge($meta, [
                'count' => $data->count()
            ]);
        } elseif ($data instanceof JsonResource) {
            // Handle single API resources
            $resourceResponse = $data->response()->getData(true);
            $response['data'] = $resourceResponse['data'] ?? [];

            if (isset($resourceResponse['meta'])) {
                $response['meta'] = array_merge($meta, $resourceResponse['meta']);
            } else {
                $response['meta'] = $meta;
            }
        } else {
            // Handle arrays and other data types
            $response['data'] = $data;

            if (!empty($meta)) {
                $response['meta'] = $meta;
            }
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Alias method for paginated responses for backward compatibility
     * and explicit pagination handling.
     *
     * @param AbstractPaginator $paginator
     * @param string|null $message
     * @param array $meta
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function paginatedResponse(
        AbstractPaginator $paginator,
        string $message = null,
        array $meta = [],
        int $statusCode = 200
    ) {
        return $this->respondWith($paginator, $message, $meta, $statusCode);
    }
}
