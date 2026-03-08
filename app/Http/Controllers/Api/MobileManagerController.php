<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerApp;
use App\Models\Sale;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class MobileManagerController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_customers' => CustomerApp::count(),
                'active_customers' => CustomerApp::where('is_blocked', false)->count(),
                'pending_orders' => Sale::whereNotNull('customer_app_id')->where('payment_status', 'pending')->count(),
                'total_orders' => Sale::whereNotNull('customer_app_id')->count(),
                'total_order_value' => Sale::whereNotNull('customer_app_id')->sum('total_amount'),
                'app_revenue' => Sale::whereNotNull('customer_app_id')
                                    ->where(function($q) {
                                        $q->where('payment_status', 'paid')
                                          ->orWhere('cancellation_fee', '>', 0);
                                    })->sum('amount_paid'),
                'recent_registrations' => CustomerApp::latest()->limit(5)->get(),
                'recent_orders' => Sale::whereNotNull('customer_app_id')
                    ->with('customerApp')
                    ->latest()
                    ->limit(5)
                    ->get(),
            ]
        ]);
    }

    public function customers()
    {
        $customers = CustomerApp::latest()->paginate(20);
        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    public function updateCustomer(Request $request, $id)
    {
        $customer = CustomerApp::findOrFail($id);
        $request->validate([
            'full_name' => 'sometimes|string|max:100',
            'phone'     => 'sometimes|string|unique:customerApp,phone,' . $id . ',customer_id',
            'status'    => 'sometimes|string',
        ]);

        $customer->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => $customer
        ]);
    }

    public function toggleBlock($id)
    {
        $customer = CustomerApp::findOrFail($id);
        $customer->is_blocked = !$customer->is_blocked;
        $customer->save();

        return response()->json([
            'success' => true,
            'message' => $customer->is_blocked ? 'Customer blocked' : 'Customer unblocked',
            'data' => $customer
        ]);
    }

    public function orders()
    {
        $orders = Sale::whereNotNull('customer_app_id')
            ->with(['customerApp', 'items.product'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $sale = Sale::whereNotNull('customer_app_id')->findOrFail($id);
        $request->validate(['status' => 'required|in:paid,cancelled,pending']);

        $oldStatus = $sale->payment_status;
        $sale->payment_status = $request->status;

        if ($request->status === 'paid' && $oldStatus !== 'paid') {
            // When completing, it's now a full sale
            $sale->amount_paid = $sale->total_amount;
            $sale->balance_due = 0;
            $sale->save();

            // Decrement customer balance (cleared debt)
            if ($sale->customerApp) {
                $sale->customerApp->decrement('current_balance', (float)$sale->total_amount);
            }

            // Record in general accounting
            $recorder = new class { use \App\Traits\FinancialRecorder; };
            $recorder->recordTransaction(
                'sale',
                $sale->total_amount,
                "Mobile App Order: {$sale->invoice_number}",
                $sale->sale_id,
                'sales',
                $sale->payment_method ?: 'cash'
            );
        } elseif ($request->status === 'cancelled' && $oldStatus !== 'cancelled') {
            // If it was pending, remove the original debt from customer balance
            if ($oldStatus === 'pending' && $sale->customerApp) {
                $sale->customerApp->decrement('current_balance', (float)$sale->total_amount);
            }

            // Apply cancellation fee
            $fee = 5.00; // Hardcoded $5 fee as requested/typical
            $sale->cancellation_fee = $fee;
            $sale->amount_paid = $fee; 
            $sale->total_amount = $fee; // Adjust total to just the fee for accounting
            $sale->balance_due = 0;
            $sale->save();

            // Record fee as income
            $recorder = new class { use \App\Traits\FinancialRecorder; };
            $recorder->recordTransaction(
                'income',
                $fee,
                "Cancellation Fee: {$sale->invoice_number}",
                $sale->sale_id,
                'sales',
                'cash'
            );
        } else {
            $sale->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Order status updated' . ($request->status === 'cancelled' ? ' with fee' : ''),
            'data' => $sale
        ]);
    }

    public function chats()
    {
        // Get unique customers who have sent messages
        $chats = ChatMessage::with('customer')
            ->select('customer_id')
            ->groupBy('customer_id')
            ->get()
            ->map(function ($chat) {
                $lastMessage = ChatMessage::where('customer_id', $chat->customer_id)
                    ->latest()
                    ->first();
                return [
                    'customer' => $chat->customer,
                    'last_message' => $lastMessage,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $chats
        ]);
    }

    public function messages($customerId)
    {
        $messages = ChatMessage::where('customer_id', $customerId)
            ->oldest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    public function sendMessage(Request $request, $customerId)
    {
        $request->validate(['message' => 'required|string']);

        $message = ChatMessage::create([
            'customer_id' => $customerId,
            'message'     => $request->message,
            'sender_type' => 'admin', // Role is admin/manager
            'is_read'     => false,
        ]);

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }
}
