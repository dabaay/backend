<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAsset extends Model
{
    use HasFactory;

    protected $primaryKey = 'asset_id';
    
    protected $fillable = [
        'asset_name',
        'purchase_date',
        'purchase_cost',
        'useful_life_years',
        'depreciation_method',
        'current_value',
        'accumulated_depreciation',
        'status',
        'notes'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'current_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
    ];
}
