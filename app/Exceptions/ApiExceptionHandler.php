<?php

namespace App\Exceptions;

use App\Traits\ApiResponseTrait;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        // First, check for JWT token error messages to provide a cleaner response
        if ($exception instanceof UnauthorizedHttpException) {
            $message = $exception->getMessage();

            // Check if error is a token error based on common patterns
            if (str_contains($message, 'token')) {
                return $this->unauthorizedResponse('Invalid authentication token');
            }

            // Handle other unauthorized errors
            return $this->unauthorizedResponse();
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

        if ($exception instanceof AuthenticationException) {
            return $this->unauthorizedResponse('Authentication required');
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
}
