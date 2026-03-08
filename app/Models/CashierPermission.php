<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class CashierPermission extends Model
{
    protected $primaryKey = 'permission_id';

    protected $fillable = [
        'cashier_id',
        'can_edit_customer',
        'can_add_expense',
        'can_edit_own_expense',
        'max_expense_amount',
        'needs_approval',
        'can_view_reports',
        'can_process_refund',
        'max_discount_percent',
        'updated_by',
    ];

    protected $casts = [
        'can_edit_customer' => 'boolean',
        'can_add_expense' => 'boolean',
        'can_edit_own_expense' => 'boolean',
        'max_expense_amount' => 'decimal:2',
        'needs_approval' => 'boolean',
        'can_view_reports' => 'boolean',
        'can_process_refund' => 'boolean',
        'max_discount_percent' => 'decimal:2',
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id', 'user_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }
}

