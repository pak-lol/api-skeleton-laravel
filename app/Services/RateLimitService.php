<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;

class RateLimitService
{
    /**
     * Maximum number of failed attempts before lockout
     */
    protected const MAX_ATTEMPTS = 5;

    /**
     * Lockout duration in minutes
     */
    protected const LOCKOUT_TIME = 5;

    /**
     * Check if the IP address is currently locked out
     *
     * @param string $ipAddress
     * @return bool
     */
    public function isIpLocked($ipAddress)
    {
        return RateLimiter::tooManyAttempts($this->getLimiterKey($ipAddress), self::MAX_ATTEMPTS);
    }

    /**
     * Get the rate limiter key for the given IP address
     *
     * @param string $ipAddress
     * @return string
     */
    protected function getLimiterKey($ipAddress)
    {
        return 'login:' . $ipAddress;
    }

    /**
     * Increment the failed login attempts for the IP address
     *
     * @param string $ipAddress
     * @return void
     */
    public function incrementFailedAttempts($ipAddress)
    {
        RateLimiter::hit($this->getLimiterKey($ipAddress), 60 * self::LOCKOUT_TIME);
    }

    /**
     * Reset the failed login attempts for the IP address
     *
     * @param string $ipAddress
     * @return void
     */
    public function resetFailedAttempts($ipAddress)
    {
        RateLimiter::clear($this->getLimiterKey($ipAddress));
    }

    /**
     * Get the remaining attempts allowed before lockout
     *
     * @param string $ipAddress
     * @return int
     */
    public function getRemainingAttempts($ipAddress)
    {
        return RateLimiter::remaining($this->getLimiterKey($ipAddress), self::MAX_ATTEMPTS);
    }

    /**
     * Get the time remaining (in seconds) for the lockout to expire
     *
     * @param string $ipAddress
     * @return int
     */
    public function getTimeRemainingForLockout($ipAddress)
    {
        return RateLimiter::availableIn($this->getLimiterKey($ipAddress));
    }
}
