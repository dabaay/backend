<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class ExpenseDocument extends Model
{
    protected $table = 'expense_documents';

    protected $primaryKey = 'document_id';

    public $timestamps = false;

    protected $fillable = [
        'expense_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
        'uploaded_by',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'expense_id', 'expense_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'user_id');
    }
}

