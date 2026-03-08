<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    
    protected $primaryKey = 'product_id';

    protected $fillable = [
        'product_code',
        'barcode',
        'name',
        'description',
        'category',
        'supplier_id',
        'cost_price',
        'selling_price',
        'wholesale_price',
        'current_stock',
        'minimum_stock',
        'maximum_stock',
        'unit',
        'location',
        'expiry_date',
        'is_active',
        'created_by',
        'image_path',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function favorites()
    {
        return $this->hasMany(CustomerFavorite::class, 'product_id', 'product_id');
    }

    public function ratings()
    {
        return $this->hasMany(ProductRating::class, 'product_id', 'product_id');
    }
}

