<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Expense extends Model
{
    protected $table = 'expenses';
    protected $primaryKey = 'expense_id';

    protected $fillable = [
        'expense_number',
        'expense_category',
        'amount',
        'description',
        'expense_date',
        'payment_method',
        'status',
        'document_path',
        'cashier_note',
        'admin_note',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by', 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public function documents()
    {
        return $this->hasMany(ExpenseDocument::class, 'expense_id', 'expense_id');
    }
}

