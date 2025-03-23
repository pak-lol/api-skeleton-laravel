<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Auth\AuthenticationException;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;

class SanctumMiddleware
{
    use ApiResponseTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            // Check if the user is authenticated
            if (!Auth::guard('sanctum')->check()) {
                return $this->unauthorizedResponse('Unauthenticated or token invalid');
            }

            // Get the authenticated user
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return $this->unauthorizedResponse('User not found');
            }

            // Token is valid, proceed with the request
            return $next($request);

        } catch (AuthenticationException $e) {
            return $this->unauthorizedResponse('Authentication failed: ' . $e->getMessage());
        } catch (Exception $e) {
            return $this->unauthorizedResponse('Authentication error: ' . $e->getMessage());
        }
    }
}
