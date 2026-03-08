<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Notification extends Model
{
    protected $primaryKey = 'notification_id';

    protected $table = 'notifications';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'message',
        'type',
        'is_read',
        'link',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}

