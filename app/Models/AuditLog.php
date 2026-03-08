<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $primaryKey = 'log_id';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'record_id',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent',
        'active_hours',
        'inactive_hours',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'active_hours' => 'decimal:2',
        'inactive_hours' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}

