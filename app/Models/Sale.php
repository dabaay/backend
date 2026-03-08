<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Sale extends Model
{
    protected $primaryKey = 'sale_id';

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'customer_app_id',
        'cashier_id',
        'sale_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'payment_method',
        'payment_status',
        'transaction_reference',
        'notes',
        'is_walpo',
        'customer_name',
        'customer_phone',
        'customer_address',
        'cancellation_fee',
        'delivery_type',
        'delivery_price',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'is_walpo' => 'boolean',
        'delivery_price' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function customerApp()
    {
        return $this->belongsTo(CustomerApp::class, 'customer_app_id', 'customer_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id', 'user_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class, 'sale_id', 'sale_id');
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'sale_id', 'sale_id');
    }

    public function debt()
    {
        return $this->hasOne(Debt::class, 'sale_id', 'sale_id');
    }
}

