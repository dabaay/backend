<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $customerId = $request->user('customer')->customer_id;

        $messages = ChatMessage::where('customer_id', $customerId)
            ->with('user:user_id,full_name')
            ->orderBy('created_at')
            ->get();

        // Mark unread messages from store as read
        ChatMessage::where('customer_id', $customerId)
            ->where('is_from_customer', false)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data'    => $messages,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $customerId = $request->user('customer')->customer_id;

        $msg = ChatMessage::create([
            'customer_id'      => $customerId,
            'user_id'          => null,
            'message'          => $request->message,
            'is_from_customer' => true,
            'is_read'          => false,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $msg,
        ], 201);
    }

    public function uploadScreenshot(Request $request)
    {
        $request->validate([
            'image'   => 'required|image|max:4096',
            'message' => 'nullable|string|max:500',
        ]);

        $customerId = $request->user('customer')->customer_id;

        $path = $request->file('image')->store('chat_images', 'public');

        $msg = ChatMessage::create([
            'customer_id'      => $customerId,
            'user_id'          => null,
            'message'          => $request->message ?? '[Image]',
            'image_path'       => $path,
            'is_from_customer' => true,
            'is_read'          => false,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $msg,
        ], 201);
    }
}
