<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerFavorite extends Model
{
    protected $table = 'customer_favorites';

    protected $fillable = [
        'customer_id',
        'product_id',
    ];

    public function customer()
    {
        return $this->belongsTo(CustomerApp::class, 'customer_id', 'customer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
