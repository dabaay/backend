<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\FinancialRecorder;

class PurchaseController extends Controller
{
    use FinancialRecorder;

    public function index()
    {
        $purchases = Purchase::with(['supplier', 'creator', 'items.product'])->orderBy('created_at', 'desc')->get();
        return response()->json($purchases);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'purchase_date' => 'required|date',
            'transport_method' => 'required|in:Bajaj,Vekon,Car,Other',
            'transport_cost' => 'required|numeric|min:0',
            'items' => 'required',
            'status' => 'required|in:pending,received,cancelled',
            'notes' => 'nullable|string',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $items = is_string($validated['items']) ? json_decode($validated['items'], true) : $validated['items'];
            
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            $documentPath = null;
            if ($request->hasFile('document')) {
                $documentPath = $request->file('document')->store('purchases', 'public');
            }

            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'],
                'total_amount' => $totalAmount,
                'transport_method' => $validated['transport_method'],
                'transport_cost' => $validated['transport_cost'],
                'purchase_date' => $validated['purchase_date'],
                'status' => $validated['status'],
                'notes' => $validated['notes'],
                'document_path' => $documentPath,
                'created_by' => Auth::id(),
            ]);

            foreach ($items as $itemData) {
                $totalPrice = $itemData['quantity'] * $itemData['unit_price'];
                
                PurchaseItem::create([
                    'purchase_id' => $purchase->purchase_id,
                    'product_name' => $itemData['product_name'] ?? null,
                    'product_id' => $itemData['product_id'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? 'piece',
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $totalPrice,
                ]);

                // Update product stock only if linked to a product and status is received
                if ($purchase->status === 'received' && !empty($itemData['product_id'])) {
                    $product = Product::find($itemData['product_id']);
                    if ($product) {
                        $product->current_stock += $itemData['quantity'];
                        $product->save();
                    }
                }
            }

            // Record transaction for items
            $this->recordTransaction(
                'purchase', 
                $totalAmount, 
                "Iibka Supplier (ID: {$purchase->purchase_id})", 
                $purchase->purchase_id, 
                'purchases',
                'cash'
            );

            // Record transaction for transport if cost > 0
            if ($purchase->transport_cost > 0) {
                $this->recordTransaction(
                    'transportation', 
                    $purchase->transport_cost, 
                    "Transportation: {$purchase->transport_method} (Purch: {$purchase->purchase_id})", 
                    $purchase->purchase_id, 
                    'purchases',
                    'cash'
                );
            }

            return response()->json($purchase->load('items.product'), 201);
        });
    }

    public function show(Purchase $purchase)
    {
        return response()->json($purchase->load(['supplier', 'creator', 'items.product']));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'purchase_date' => 'required|date',
            'transport_method' => 'required|in:Bajaj,Vekon,Car,Other',
            'transport_cost' => 'required|numeric|min:0',
            'items' => 'required',
            'status' => 'required|in:pending,received,cancelled',
            'notes' => 'nullable|string',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        return DB::transaction(function () use ($validated, $request, $purchase) {
            // 1. Revert stock if previous status was 'received'
            if ($purchase->status === 'received') {
                foreach ($purchase->items as $item) {
                    if ($item->product_id) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $product->current_stock -= $item->quantity;
                            $product->save();
                        }
                    }
                }
            }

            $items = is_string($validated['items']) ? json_decode($validated['items'], true) : $validated['items'];
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            // 2. Handle Document replacement
            $documentPath = $purchase->document_path;
            if ($request->hasFile('document')) {
                // Delete old document if exists
                if ($documentPath && \Storage::disk('public')->exists($documentPath)) {
                    \Storage::disk('public')->delete($documentPath);
                }
                $documentPath = $request->file('document')->store('purchases', 'public');
            }

            // 3. Update Purchase record
            $purchase->update([
                'supplier_id' => $validated['supplier_id'],
                'total_amount' => $totalAmount,
                'transport_method' => $validated['transport_method'],
                'transport_cost' => $validated['transport_cost'],
                'purchase_date' => $validated['purchase_date'],
                'status' => $validated['status'],
                'notes' => $validated['notes'],
                'document_path' => $documentPath,
            ]);

            // 4. Cleanup and Refresh items
            $purchase->items()->delete();
            foreach ($items as $itemData) {
                $totalPrice = $itemData['quantity'] * $itemData['unit_price'];
                
                PurchaseItem::create([
                    'purchase_id' => $purchase->purchase_id,
                    'product_name' => $itemData['product_name'] ?? null,
                    'product_id' => $itemData['product_id'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? 'piece',
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $totalPrice,
                ]);

                // 5. Update product stock if status is 'received'
                if ($purchase->status === 'received' && !empty($itemData['product_id'])) {
                    $product = Product::find($itemData['product_id']);
                    if ($product) {
                        $product->current_stock += $itemData['quantity'];
                        $product->save();
                    }
                }
            }

            // 6. Adjust financial transactions - Reverse old impact via type-specific reversal
            // (Simpler: delete old related transactions and record new ones)
            DB::table('financial_transactions')
              ->where('reference_id', $purchase->purchase_id)
              ->where('reference_table', 'purchases')
              ->delete();

            $this->recordTransaction(
                'purchase', 
                $totalAmount, 
                "Updated Iibka Supplier (ID: {$purchase->purchase_id})", 
                $purchase->purchase_id, 
                'purchases',
                'cash'
            );

            if ($purchase->transport_cost > 0) {
                $this->recordTransaction(
                    'transportation', 
                    $purchase->transport_cost, 
                    "Updated Transportation: {$purchase->transport_method} (Purch: {$purchase->purchase_id})", 
                    $purchase->purchase_id, 
                    'purchases',
                    'cash'
                );
            }

            return response()->json($purchase->load('items.product'));
        });
    }

    public function destroy(Purchase $purchase)
    {
        return DB::transaction(function () use ($purchase) {
            // 1. Revert stock if status was 'received'
            if ($purchase->status === 'received') {
                foreach ($purchase->items as $item) {
                    if ($item->product_id) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $product->current_stock -= $item->quantity;
                            $product->save();
                        }
                    }
                }
            }

            // 2. Delete document file
            if ($purchase->document_path && \Storage::disk('public')->exists($purchase->document_path)) {
                \Storage::disk('public')->delete($purchase->document_path);
            }

            // 3. Delete associated financial transactions
            DB::table('financial_transactions')
              ->where('reference_id', $purchase->purchase_id)
              ->where('reference_table', 'purchases')
              ->delete();

            // 4. Delete items and purchase
            $purchase->items()->delete();
            $purchase->delete();

            return response()->json(['message' => 'Iibka waa la tirtiray.']);
        });
    }
}
