<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\FinancialRecorder;

class SaleController extends Controller
{
    use FinancialRecorder;

    public function index()
    {
        $sales = Sale::with(['customer', 'cashier', 'items.product', 'debt.payments'])->latest()->paginate(50);
        return response()->json($sales);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,customer_id',
            'subtotal' => 'required|numeric',
            'discount_amount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'total_amount' => 'required|numeric',
            'amount_paid' => 'required|numeric',
            'payment_method' => 'required|in:cash,evc_plus,shilin_somali',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,product_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric',
            'items.*.subtotal' => 'required|numeric',
        ]);

        return DB::transaction(function () use ($validated) {
            $sale = Sale::create([
                'invoice_number' => 'INV-' . strtoupper(uniqid()),
                'customer_id' => $validated['customer_id'],
                'cashier_id' => Auth::id(),
                'sale_date' => now(),
                'subtotal' => $validated['subtotal'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'total_amount' => $validated['total_amount'],
                'amount_paid' => $validated['amount_paid'],
                'balance_due' => $validated['total_amount'] - $validated['amount_paid'],
                'payment_method' => $validated['payment_method'],
                'payment_status' => ($validated['amount_paid'] >= $validated['total_amount']) ? 'paid' : 'partial',
            ]);

            foreach ($validated['items'] as $item) {
                // Update stock and get product info
                $product = Product::findOrFail($item['product_id']);
                
                $sale->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'cost_price' => $product->cost_price, // Added missing cost_price
                ]);

                $product->decrement('current_stock', $item['quantity']);
            }

            // If partial payment, create debt record
            if ($sale->balance_due > 0 && $sale->customer_id) {
                $sale->customer->debts()->create([
                    'sale_id' => $sale->sale_id,
                    'original_amount' => $sale->balance_due,
                    'remaining_amount' => $sale->balance_due,
                    'due_date' => now()->addDays(30), // Default 30 days
                    'status' => 'active',
                ]);
                $sale->customer->increment('current_balance', (float) $sale->balance_due);
            }

            // Record in accounting ledger
            $this->recordTransaction(
                'sale', 
                $sale->amount_paid, 
                "Iibka Invoice: {$sale->invoice_number}" . ($sale->balance_due > 0 ? " (Partial)" : ""), 
                $sale->sale_id, 
                'sales',
                $sale->payment_method,
                $sale->balance_due
            );

            return response()->json($sale->load('items'), 201);
        });
    }

    public function show(Sale $sale)
    {
        return response()->json($sale->load(['customer', 'cashier', 'items.product']));
    }

    public function destroy(Sale $sale)
    {
        return DB::transaction(function () use ($sale) {
            // 1. Return items to stock
            foreach ($sale->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('current_stock', $item->quantity);
                }
            }

            // 2. Handle Debt reversal if any
            if ($sale->customer_id && $sale->balance_due > 0) {
                $sale->customer->decrement('current_balance', (float)$sale->balance_due);
                // Debt records will be deleted by cascading or manual check
                $sale->customer->debts()->where('sale_id', $sale->sale_id)->delete();
            }

            // 3. Remove from accounting ledger
            $this->removeTransaction($sale->sale_id, 'sales');

            // 4. Delete the sale
            $sale->delete();

            return response()->json(['message' => 'Iibka si guul leh ayaa loo tirtiray, alaabtiina waa la soo celiyay.'], 200);
        });
    }
}
