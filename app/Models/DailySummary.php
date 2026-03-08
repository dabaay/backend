<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class DailySummary extends Model
{
    protected $table = 'daily_summaries';

    protected $primaryKey = 'summary_id';

    protected $fillable = [
        'summary_date',
        'total_sales',
        'total_cash_sales',
        'total_evc_sales',
        'total_shilin_sales',
        'total_expenses',
        'total_purchases',
        'total_transportation',
        'total_debt_collected',
        'total_debt_created',
        'total_profit',
        'transaction_count',
        'customer_count',
    ];

    protected $casts = [
        'summary_date' => 'date',
        'total_sales' => 'decimal:2',
        'total_cash_sales' => 'decimal:2',
        'total_evc_sales' => 'decimal:2',
        'total_shilin_sales' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'total_purchases' => 'decimal:2',
        'total_transportation' => 'decimal:2',
        'total_debt_collected' => 'decimal:2',
        'total_debt_created' => 'decimal:2',
        'total_profit' => 'decimal:2',
    ];
}

