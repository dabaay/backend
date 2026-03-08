<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class PurchaseItem extends Model
{
    protected $primaryKey = 'purchase_item_id';

    protected $fillable = [
        'purchase_id',
        'product_name',
        'product_id',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id', 'purchase_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}

