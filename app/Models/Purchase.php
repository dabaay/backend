<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Purchase extends Model
{
    protected $table = 'purchases';
    protected $primaryKey = 'purchase_id';

    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'total_amount',
        'transport_method',
        'transport_cost',
        'purchase_date',
        'status',
        'notes',
        'document_path',
        'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'transport_cost' => 'decimal:2',
        'purchase_date' => 'date',
    ];

    protected $appends = ['document_url'];

    public function getDocumentUrlAttribute()
    {
        return $this->document_path ? asset('storage/' . $this->document_path) : null;
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id', 'purchase_id');
    }
}

