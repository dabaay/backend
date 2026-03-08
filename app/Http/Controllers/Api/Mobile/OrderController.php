<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Sale::where('customer_app_id', $request->user('customer')->customer_id)
            ->with(['saleItems.product'])
            ->orderByDesc('sale_date')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|integer|exists:products,product_id',
            'items.*.quantity'      => 'required|integer|min:1',
            'payment_method'        => 'required|in:cash,evc_plus,shilin_somali',
            'transaction_reference' => 'nullable|string|max:100',
            'customer_name'         => 'sometimes|required_without:customer_id|string|max:100',
            'customer_phone'        => 'sometimes|required_without:customer_id|string|max:20',
            'customer_address'      => 'required|string',
        ]);

        $customer = $request->user('customer');

        return DB::transaction(function () use ($request, $customer) {
            $items    = $request->items;
            $subtotal = 0;
            $itemsData = [];

            foreach ($items as $item) {
                $product = Product::where('is_active', true)->findOrFail($item['product_id']);

                if ($product->current_stock < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for: {$product->name}",
                    ], 422);
                }

                $lineTotal   = $product->selling_price * $item['quantity'];
                $subtotal   += $lineTotal;
                $itemsData[] = [
                    'product'    => $product,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $product->selling_price,
                    'cost_price' => $product->cost_price,
                    'subtotal'   => $lineTotal,
                ];
            }

            $invoiceNumber = 'MOB-' . strtoupper(uniqid());

            $sale = Sale::create([
                'invoice_number'        => $invoiceNumber,
                'customer_app_id'       => $customer->customer_id,
                'customer_id'           => null, // Not a POS customer
                'cashier_id'            => null,   // Assigned when cashier approves
                'sale_date'             => now(),
                'subtotal'              => $subtotal,
                'discount_amount'       => 0,
                'tax_amount'            => 0,
                'total_amount'          => $subtotal,
                'amount_paid'           => 0,
                'balance_due'           => $subtotal,
                'payment_method'        => $request->payment_method,
                'payment_status'        => 'pending',
                'transaction_reference' => $request->transaction_reference,
                'is_walpo'              => false,
                'notes'                 => 'Order placed via Mobile App.',
                'customer_name'         => $request->customer_name ?? $customer->full_name,
                'customer_phone'        => $request->customer_phone ?? $customer->phone,
                'customer_address'      => $request->customer_address,
            ]);

            // Increment customer balance (debt)
            if ($customer) {
                $customer->increment('current_balance', (float)$subtotal);
            }

            foreach ($itemsData as $itemData) {
                SaleItem::create([
                    'sale_id'          => $sale->sale_id,
                    'product_id'       => $itemData['product']->product_id,
                    'quantity'         => $itemData['quantity'],
                    'unit_price'       => $itemData['unit_price'],
                    'discount_percent' => 0,
                    'subtotal'         => $itemData['subtotal'],
                    'cost_price'       => $itemData['cost_price'],
                ]);

                $itemData['product']->decrement('current_stock', $itemData['quantity']);
            }

            return response()->json([
                'success' => true,
                'data'    => $sale->load('saleItems.product'),
                'message' => 'Order placed successfully. Awaiting cashier approval.',
            ], 201);
        });
    }

    public function show(Request $request, $id)
    {
        $order = Sale::where('customer_app_id', $request->user('customer')->customer_id)
            ->with(['saleItems.product'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $order,
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $order = Sale::where('customer_app_id', $request->user('customer')->customer_id)
            ->where('payment_status', 'pending')
            ->with('saleItems.product')
            ->findOrFail($id);

        DB::transaction(function () use ($order) {
            // Return stock
            foreach ($order->saleItems as $item) {
                if ($item->product) {
                    $item->product->increment('current_stock', $item->quantity);
                }
            }

            $order->payment_status = 'cancelled';
            $order->save();

            // Decrement customer balance (remove debt)
            if ($order->customerApp) {
                $order->customerApp->decrement('current_balance', (float)$order->total_amount);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully',
        ]);
    }
}
