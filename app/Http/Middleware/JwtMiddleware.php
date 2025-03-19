<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Traits\ApiResponseTrait;

class JwtMiddleware
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
            // Check for token
            if (!$token = JWTAuth::getToken()) {
                return $this->unauthorizedResponse('Token not provided');
            }

            // Try to authenticate user
            $user = JWTAuth::authenticate($token);

            if (!$user) {
                return $this->unauthorizedResponse('User not found');
            }

        } catch (TokenExpiredException $e) {
            return $this->unauthorizedResponse('Token has expired');
        } catch (TokenInvalidException $e) {
            return $this->unauthorizedResponse('Token is invalid');
        } catch (TokenBlacklistedException $e) {
            return $this->unauthorizedResponse('Token has been blacklisted');
        } catch (JWTException $e) {
            return $this->unauthorizedResponse('Token error: ' . $e->getMessage());
        } catch (Exception $e) {
            return $this->unauthorizedResponse('Authentication error');
        }
        return $next($request);
    }
}
