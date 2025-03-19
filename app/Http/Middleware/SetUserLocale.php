<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\ApiResponseTrait;
use Tymon\JWTAuth\Facades\JWTAuth;


class SetUserLocale
{
    use ApiResponseTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Default language is English
            app()->setLocale('en');

            // Check if we have an authenticated user
            if ($request->bearerToken()) {
                // Try to get the authenticated user, but don't throw exceptions
                $user = JWTAuth::parseToken()->authenticate();

                // If we have a user and the user has a locale, set it

                if ($user && $user->locale) {
                    app()->setLocale($user->locale);
                } else{
                    app()->setLocale('en');
                }
            }
        } catch (\Exception $e) {
            // If any exception occurs, just log it and ensure English is set as fallback
            \Log::error('Failed to set user locale: ' . $e->getMessage());
            app()->setLocale('en');
        }

        return $next($request);
    }
}
