<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Debt extends Model
{
    protected $primaryKey = 'debt_id';

    protected $fillable = [
        'customer_id',
        'sale_id',
        'original_amount',
        'remaining_amount',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'sale_id');
    }

    public function payments()
    {
        return $this->hasMany(DebtPayment::class, 'debt_id', 'debt_id');
    }
}

