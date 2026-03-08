<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class CustomerApp extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'customerApp';
    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'full_name',
        'username',
        'phone',
        'address',
        'password',
        'profile_image',
        'status',
        'registration_date',
        'current_balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'registration_date' => 'datetime',
        'current_balance'   => 'decimal:2',
        'password'          => 'hashed',
    ];

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function favorites()
    {
        return $this->hasMany(CustomerFavorite::class, 'customer_id', 'customer_id');
    }

    public function ratings()
    {
        return $this->hasMany(ProductRating::class, 'customer_id', 'customer_id');
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class, 'customer_id', 'customer_id');
    }
}
