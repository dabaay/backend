<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapitalTransaction extends Model
{
    use HasFactory;

    protected $primaryKey = 'capital_id';

    protected $fillable = [
        'transaction_date',
        'transaction_type',
        'amount',
        'description',
        'created_by'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
