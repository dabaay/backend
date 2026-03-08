<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class PaymentLog extends Model
{
    protected $table = 'payment_logs';
    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'amount_paid',
        'request_data',
        'response_data',
        'status',
        'reference',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];
}

