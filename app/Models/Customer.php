<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use SoftDeletes, HasApiTokens;

    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'full_name',
        'username',
        'phone',
        'address',
        'id_number',
        'credit_limit',
        'current_balance',
        'total_purchases',
        'last_purchase_date',
        'status',
        'registered_by',
        'notes',
        'password',
        'is_mobile_user',
        'profile_image',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'credit_limit'      => 'decimal:2',
        'current_balance'   => 'decimal:2',
        'total_purchases'   => 'decimal:2',
        'last_purchase_date'=> 'date',
        'registration_date' => 'datetime',
        'is_mobile_user'    => 'boolean',
        'password'          => 'hashed',
    ];

    /**
     * Tell Sanctum/Laravel Auth to use 'password' (not password_hash) for this model.
     */
    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function registrar()
    {
        return $this->belongsTo(User::class, 'registered_by', 'user_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'customer_id', 'customer_id');
    }

    public function debts()
    {
        return $this->hasMany(Debt::class, 'customer_id', 'customer_id');
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
