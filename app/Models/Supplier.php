<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Supplier extends Model
{
    protected $primaryKey = 'supplier_id';

    protected $table = 'suppliers';

    protected $fillable = [
        'company_name',
        'contact_person',
        'phone',
        'email',
        'address',
        'tax_number',
        'contract_start',
        'contract_end',
        'status',
        'notes',
        'document_path',
    ];

    protected $casts = [
        'contract_start' => 'date',
        'contract_end' => 'date',
    ];
}

