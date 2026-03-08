<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\FinancialRecorder;

class WalpoController extends Controller
{
    use FinancialRecorder;

    public function index()
    {
        $walpoSales = Sale::with(['customer', 'cashier', 'items.product', 'debt'])
            ->where('is_walpo', true)
            ->latest()
            ->paginate(50);
        
        return response()->json($walpoSales);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,customer_id',
            'subtotal' => 'required|numeric',
            'discount_amount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'total_amount' => 'required|numeric',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,product_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric',
            'items.*.subtotal' => 'required|numeric',
        ]);

        return DB::transaction(function () use ($validated) {
            // Create Walpo sale
            $sale = Sale::create([
                'invoice_number' => 'WALPO-' . strtoupper(uniqid()),
                'customer_id' => $validated['customer_id'],
                'cashier_id' => Auth::id(),
                'sale_date' => now(),
                'subtotal' => $validated['subtotal'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'total_amount' => $validated['total_amount'],
                'amount_paid' => 0, // Walpo is always credit
                'balance_due' => $validated['total_amount'],
                'payment_method' => 'credit',
                'payment_status' => 'credit',
                'is_walpo' => true,
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                $sale->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'taken_quantity' => 0, // Initially nothing taken
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'cost_price' => $product->cost_price,
                ]);

                // Stock will be reduced when items are actually taken
            }

            // Create debt record
            $sale->customer->debts()->create([
                'sale_id' => $sale->sale_id,
                'original_amount' => $sale->total_amount,
                'remaining_amount' => $sale->total_amount,
                'due_date' => now()->addDays(30),
                'status' => 'pending',
            ]);

            $sale->customer->increment('current_balance', (float) $sale->total_amount);

            // Record in accounting ledger
            $this->recordTransaction(
                'walpo_created', 
                0, 
                "Walpo Invoice: {$sale->invoice_number} - {$sale->customer->full_name}", 
                $sale->sale_id, 
                'sales',
                'credit',
                $sale->total_amount
            );

            return response()->json($sale->load('items.product'), 201);
        });
    }


    public function show(Sale $sale)
    {
        if (!$sale->is_walpo) {
            return response()->json(['message' => 'This is not a Walpo sale'], 400);
        }

        return response()->json($sale->load(['customer', 'cashier', 'items.product']));
    }

    public function getCustomerHistory($customerId)
    {
        $mainCustomer = Customer::findOrFail($customerId);
        $phone = $mainCustomer->phone;

        // Find all customer IDs (including soft-deleted) with the same phone
        $customerIds = Customer::withTrashed()
            ->where('phone', $phone)
            ->pluck('customer_id')
            ->toArray();

        // Get all Walpo sales for all these customer IDs
        $walpoSales = Sale::with(['items.product', 'cashier', 'debt.payments'])
            ->whereIn('customer_id', $customerIds)
            ->where('is_walpo', true)
            ->orderBy('sale_date', 'desc')
            ->get();

        // Get debts for all matching customers
        $debts = \App\Models\Debt::with(['payments', 'sale.items.product'])
            ->whereIn('customer_id', $customerIds)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate totals
        $totalAmount = $walpoSales->sum('total_amount');
        $totalPaid = $walpoSales->sum('amount_paid');
        $totalBalance = $walpoSales->sum('balance_due');
        $totalItems = $walpoSales->sum(function($sale) {
            return $sale->items->sum('quantity');
        });
        $totalTaken = $walpoSales->sum(function($sale) {
            return $sale->items->sum('taken_quantity');
        });

        // Get last payment date across all matching records
        $lastPaymentDate = null;
        $allPayments = $debts->flatMap->payments;
        if ($allPayments->count() > 0) {
            $lastPaymentDate = $allPayments->max('payment_date');
        }

        return response()->json([
            'customer' => $mainCustomer,
            'customer_ids' => $customerIds, // For reference
            'sales' => $walpoSales,
            'summary' => [
                'total_amount' => $totalAmount,
                'total_paid' => $totalPaid,
                'total_balance' => $totalBalance,
                'total_items_ordered' => $totalItems,
                'total_items_taken' => $totalTaken,
                'total_sales' => $walpoSales->count(),
                'last_payment_date' => $lastPaymentDate,
            ]
        ]);
    }
}
