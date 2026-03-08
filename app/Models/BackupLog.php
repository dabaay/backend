<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class BackupLog extends Model
{
    protected $table = 'backup_logs';

    protected $primaryKey = 'backup_id';

    public $timestamps = false;

    protected $fillable = [
        'file_name',
        'file_path',
        'file_size',
        'status',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'backup_date' => 'datetime',
        'file_size' => 'integer',
        'created_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}

