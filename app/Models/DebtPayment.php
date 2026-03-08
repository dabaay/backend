<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class DebtPayment extends Model
{
    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'debt_id',
        'amount_paid',
        'payment_method',
        'transaction_reference',
        'payment_date',
        'received_by',
        'notes',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    public function debt()
    {
        return $this->belongsTo(Debt::class, 'debt_id', 'debt_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by', 'user_id');
    }
}

