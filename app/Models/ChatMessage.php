<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $table = 'chat_messages';

    protected $fillable = [
        'customer_id',
        'user_id',
        'message',
        'image_path',
        'is_from_customer',
        'is_read',
    ];

    protected $casts = [
        'is_from_customer' => 'boolean',
        'is_read' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(CustomerApp::class, 'customer_id', 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
