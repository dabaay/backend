<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class FinancialTransaction extends Model
{
    protected $primaryKey = 'transaction_id';

    public $timestamps = false;

    protected $fillable = [
        'transaction_date',
        'transaction_type',
        'reference_id',
        'reference_table',
        'debit',
        'credit',
        'balance',
        'description',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}

