<?php

namespace App\Exceptions;

use App\Traits\ApiResponseTrait;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class ApiExceptionHandler extends ExceptionHandler
{
    use ApiResponseTrait;

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('*')) {
                return $this->handleApiException($e);
            }
        });
    }

    /**
     * Handle API exceptions.
     *
     * @param \Throwable $exception
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleApiException(Throwable $exception)
    {
        // Handle rate limiting exceptions
        if ($exception instanceof ThrottleRequestsException ||
            $exception instanceof TooManyRequestsHttpException) {
            return $this->rateLimitExceededResponse($exception);
        }

        // Handle Sanctum's missing ability exception
        if ($exception instanceof MissingAbilityException) {
            return $this->forbiddenResponse('You do not have the required permissions for this action.');
        }

        // Handle authentication exceptions
        if ($exception instanceof UnauthorizedHttpException ||
            $exception instanceof AuthenticationException) {
            // More generic authentication error message that works for both JWT and Sanctum
            return $this->unauthorizedResponse(__('messages.unauthenticated'));
        }

        if ($exception instanceof ValidationException) {
            return $this->validationErrorResponse(
                $exception->errors(),
                'Validation failed'
            );
        }

        if ($exception instanceof ModelNotFoundException) {
            $modelName = strtolower(class_basename($exception->getModel()));
            return $this->notFoundResponse("{$modelName} not found");
        }

        if ($exception instanceof NotFoundHttpException) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return $this->errorResponse('The specified method is not allowed', null, 405);
        }

        if ($exception instanceof AccessDeniedHttpException) {
            return $this->forbiddenResponse();
        }

        // Handle any other exceptions
        $debug = config('app.debug');

        // In production, don't expose error details
        if (!$debug) {
            return $this->serverErrorResponse('Server Error');
        }

        // Only in debug mode, include more details
        return $this->serverErrorResponse($exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }

    /**
     * Generate a beautiful response for rate limit exceeded errors
     *
     * @param \Throwable $exception
     * @return \Illuminate\Http\JsonResponse
     */
    private function rateLimitExceededResponse(Throwable $exception)
    {
        // Get retry after value if available
        $retryAfter = 60; // Default fallback value

        if (method_exists($exception, 'getHeaders')) {
            $headers = $exception->getHeaders();
            $retryAfter = $headers['Retry-After'] ?? $retryAfter;
        }

        // Calculate when the user can try again
        $retryDate = now()->addSeconds($retryAfter);

        // Format the date in a user-friendly way
        $retryDateFormatted = $retryDate->diffForHumans();

        // Custom, user-friendly error message
        $message = __('messages.rate_limit_exceeded', [
            'time' => $retryDateFormatted
        ]) ?? "API rate limit exceeded. Please try again {$retryDateFormatted}.";

        // Build a structured response
        $data = [
            'success' => false,
            'message' => $message,
            'errors' => [
                'rate_limit' => [
                    'retry_after_seconds' => (int) $retryAfter,
                    'retry_after_time' => $retryDateFormatted,
                    'retry_after_timestamp' => $retryDate->toIso8601String(),
                ]
            ],
            'status_code' => 429,
            'timestamp' => now()->toIso8601String()
        ];

        // Return with proper headers
        $headers = [
            'Retry-After' => $retryAfter,
            'X-RateLimit-Reset' => $retryDate->getTimestamp(),
        ];

        return response()->json($data, 429, $headers);
    }
}
