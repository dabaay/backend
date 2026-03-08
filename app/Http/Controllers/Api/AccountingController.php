<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailySummary;
use App\Models\FinancialTransaction;
use App\Models\Debt;
use App\Models\DebtPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Traits\FinancialRecorder;

class AccountingController extends Controller
{
    use FinancialRecorder;

    public function transactions()
    {
        return response()->json(FinancialTransaction::with('creator')->latest()->take(100)->get());
    }

    public function dailySummaries()
    {
        return response()->json(DailySummary::latest()->take(30)->get());
    }

    public function debts()
    {
        return response()->json(Debt::with('customer', 'sale')->latest()->get());
    }

    public function debtPayments()
    {
        return response()->json(DebtPayment::with('debt.customer', 'receiver')->latest()->get());
    }

    public function storeDebtPayment(Request $request)
    {
        $validated = $request->validate([
            'debt_id' => 'required|exists:debts,debt_id',
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,evc_plus,shilin_somali',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $debt = Debt::findOrFail($validated['debt_id']);
            
            if ($validated['amount_paid'] > $debt->remaining_amount) {
                return response()->json(['message' => 'Lacagta la bixiyey way ka badantahay deynta taalo.'], 422);
            }

            $payment = DebtPayment::create([
                'debt_id' => $debt->debt_id,
                'amount_paid' => $validated['amount_paid'],
                'payment_date' => now(),
                'payment_method' => $validated['payment_method'],
                'received_by' => Auth::id(),
                'notes' => $validated['notes'],
            ]);

            $debt->decrement('remaining_amount', $validated['amount_paid']);
            if ($debt->remaining_amount <= 0) {
                $debt->update(['status' => 'paid']);
            }

            // Sync with Sale record
            if ($debt->sale) {
                $debt->sale->increment('amount_paid', $validated['amount_paid']);
                $debt->sale->decrement('balance_due', $validated['amount_paid']);
                if ($debt->sale->balance_due <= 0) {
                    $debt->sale->update(['payment_status' => 'paid']);
                } else {
                    $debt->sale->update(['payment_status' => 'partial']);
                }
            }

            // Update customer balance
            $debt->customer->decrement('current_balance', $validated['amount_paid']);

            // Record in accounting
            $this->recordTransaction(
                'debt_collection',
                $validated['amount_paid'],
                "Debt Collection: {$debt->customer->full_name} (Inv: {$debt->sale->invoice_number})",
                $payment->payment_id,
                'debt_payments',
                $validated['payment_method']
            );

            return response()->json($payment, 201);
        });
    }
}
