<?php
  
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Traits\FinancialRecorder;

class ExpenseController extends Controller
{
    use FinancialRecorder;

    public function index()
    {
        $expenses = Expense::with(['requester', 'approver'])
            ->orderBy('expense_date', 'desc')
            ->get()
            ->map(function (\App\Models\Expense $e) {
                return array_merge($e->toArray(), [
                    'source' => 'expenses',
                    'requester' => $e->requester,
                    'document_url' => $e->document_path ? asset('storage/' . $e->document_path) : null,
                ]);
            });

        // Fetch purchases and transportation transactions from financial_transactions
        $transactions = \App\Models\FinancialTransaction::whereIn('transaction_type', ['purchase', 'transportation'])
            ->orderBy('transaction_date', 'desc')
            ->get()
            ->map(function ($t) {
                $purchase = null;
                if ($t->reference_table === 'purchases') {
                    $purchase = \App\Models\Purchase::find($t->reference_id);
                }

                return [
                    'expense_id' => 'tx-' . $t->transaction_id,
                    'expense_number' => ($t->transaction_type === 'purchase' ? 'PRCH-' : 'TRNS-') . $t->transaction_id,
                    'expense_category' => $t->transaction_type === 'purchase' ? 'purchase' : 'transport',
                    'amount' => $t->debit, // Always debit for these types
                    'description' => $t->description,
                    'expense_date' => $t->transaction_date->toDateString(),
                    'payment_method' => 'cash', // Default based on FinancialRecorder logic
                    'status' => 'approved',
                    'source' => 'financial_transactions',
                    'document_path' => $purchase ? $purchase->document_path : null,
                    'document_url' => $purchase ? asset('storage/' . $purchase->document_path) : null,
                    'requester' => $t->creator,
                    'reference_id' => $t->reference_id,
                    'reference_table' => $t->reference_table,
                ];
            });

        // Merge and sort
        $merged = $expenses->concat($transactions)->sortByDesc('expense_date')->values();

        return response()->json($merged);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_category' => 'required|in:rent,electricity,water,tax,salary,maintenance,transport,marketing,office,other',
            'amount' => 'required|numeric',
            'description' => 'required|string',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank,evc_plus',
            'cashier_note' => 'nullable|string',
        ]);

        $validated['requested_by'] = Auth::id();
        $validated['expense_number'] = 'EXP-' . strtoupper(uniqid());
        $validated['status'] = 'pending';

        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('expenses', 'public');
            $validated['document_path'] = $path;
        }

        $expense = Expense::create($validated);

        // Record in accounting ledger
        $type = $expense->expense_category === 'transport' ? 'transportation' : 'expense';
        $this->recordTransaction(
            $type, 
            $expense->amount, 
            "Kharash: {$expense->description} ({$expense->expense_number})", 
            $expense->expense_id, 
            'expenses',
            $expense->payment_method
        );

        return response()->json($expense, 201);
    }

    public function show(Expense $expense)
    {
        return response()->json($expense->load(['requester', 'approver', 'documents']));
    }

    public function update(Request $request, Expense $expense)
    {
        $isAdmin = Auth::user()->role === 'admin';

        // Non-admins can only edit pending expenses
        if (!$isAdmin && $expense->status !== 'pending') {
            return response()->json(['message' => 'Kharashkan lama bedeli karo sababtoo ah waa la go\'aamiyay.'], 403);
        }

        $validated = $request->validate([
            'expense_category' => 'sometimes|required|in:rent,electricity,water,tax,salary,maintenance,transport,marketing,office,other',
            'amount' => 'sometimes|required|numeric',
            'description' => 'sometimes|required|string',
            'expense_date' => 'sometimes|required|date',
            'payment_method' => 'sometimes|required|in:cash,bank,evc_plus',
            'status' => 'sometimes|in:pending,approved,rejected',
            'rejection_reason' => 'nullable|string',
            'admin_note' => 'nullable|string',
        ]);

        // Only admins can change status
        if (isset($validated['status']) && !$isAdmin) {
            unset($validated['status']);
        }

        if (isset($validated['status']) && $validated['status'] !== $expense->status) {
            if ($validated['status'] === 'approved') {
                $validated['approved_by'] = Auth::id();
                $validated['approved_at'] = now();
            }
        }

        if ($request->hasFile('document')) {
            if ($expense->document_path) {
                Storage::disk('public')->delete($expense->document_path);
            }
            $path = $request->file('document')->store('expenses', 'public');
            $validated['document_path'] = $path;
        }

        $expense->update($validated);
        return response()->json($expense);
    }

    public function destroy(Expense $expense)
    {
        $isAdmin = Auth::user()->role === 'admin';

        // Only allow deleting manual expenses from here
        // (Purchases/Transport are virtual and shouldn't be deletable via Expense model)

        if (!$isAdmin && $expense->status !== 'pending') {
            return response()->json(['message' => 'Kharashkan lama tirtiri karo sababtoo ah waa la go\'aamiyay.'], 403);
        }

        // Remove from accounting ledger
        $this->removeTransaction($expense->expense_id, 'expenses');

        if ($expense->document_path) {
            Storage::disk('public')->delete($expense->document_path);
        }
        $expense->delete();
        return response()->json(null, 204);
    }
}
