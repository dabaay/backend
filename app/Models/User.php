<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'password_hash',
        'full_name',
        'email',
        'phone',
        'role',
        'permissions',
        'max_discount_percent',
        'needs_approval',
        'created_by',
        'is_enabled',
        'is_online',
        'last_login',
        'last_logout_at',
        'plain_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_hash' => 'hashed',
            'last_login' => 'datetime',
            'last_logout_at' => 'datetime',
            'is_enabled' => 'boolean',
            'is_online' => 'boolean',
            'permissions' => 'array',
        ];
    }

    /**
     * Check if user has access to a specific module
     */
    public function hasModuleAccess(string $moduleKey): bool
    {
        if ($this->role === 'admin') return true;
        
        $permissions = $this->permissions ?? [];
        return !empty($permissions['modules'][$moduleKey]);
    }

    /**
     * Check if user can perform a specific action
     */
    public function canPerformAction(string $actionKey): bool
    {
        if ($this->role === 'admin') return true;

        $permissions = $this->permissions ?? [];
        return !empty($permissions['actions'][$actionKey]);
    }
}

