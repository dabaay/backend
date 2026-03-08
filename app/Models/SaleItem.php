<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class SaleItem extends Model
{
    protected $table = 'sale_items';
    protected $primaryKey = 'sale_item_id';

    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'taken_quantity',
        'unit_price',
        'discount_percent',
        'subtotal',
        'cost_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'cost_price' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'sale_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}

