<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the refresh tokens associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function refreshTokens()
    {
        return $this->hasMany(RefreshToken::class);
    }

    /**
     * Get valid (non-revoked and non-expired) refresh tokens for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function validRefreshTokens()
    {
        return $this->refreshTokens()->valid();
    }

    /**
     * Create a new refresh token for the user.
     *
     * @param array $attributes
     * @return \App\Models\RefreshToken
     */
    public function createRefreshToken(array $attributes)
    {
        return $this->refreshTokens()->create($attributes);
    }

    /**
     * Revoke all refresh tokens for the user.
     *
     * @return int The number of tokens revoked
     */
    public function revokeAllRefreshTokens()
    {
        return $this->refreshTokens()->update(['revoked' => true]);
    }

    /**
     * Revoke refresh tokens for a specific device.
     *
     * @param string $device
     * @return int The number of tokens revoked
     */
    public function revokeRefreshTokensForDevice($device)
    {
        return $this->refreshTokens()
            ->where('device', $device)
            ->update(['revoked' => true]);
    }

    /**
     * Check if the user has a specific role
     *
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Check if the user is an admin
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }
}
