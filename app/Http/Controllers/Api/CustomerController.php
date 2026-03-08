<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with(['debts.payments'])->paginate(50);
        
        $customers->getCollection()->transform(function($customer) {
            $customer->last_payment_date = $customer->debts->flatMap->payments->max('payment_date');
            return $customer;
        });

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|max:200',
            'phone' => 'required|max:20',
            'address' => 'nullable',
            'id_number' => 'nullable|max:50',
            'credit_limit' => 'nullable|numeric',
            'notes' => 'nullable',
        ]);

        // Support persistent history: If a customer with this phone exists (including deleted), restore/reuse it
        $customer = Customer::withTrashed()->where('phone', $validated['phone'])->first();

        if ($customer) {
            if ($customer->trashed()) {
                $customer->restore();
            }
            $customer->update($validated);
            return response()->json($customer, 201);
        }

        $validated['registered_by'] = Auth::id();
        $validated['registration_date'] = now();

        $customer = Customer::create($validated);
        return response()->json($customer, 201);
    }

    public function show(Customer $customer)
    {
        return response()->json($customer->load(['registrar']));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'full_name' => 'sometimes|required|max:200',
            'phone' => 'sometimes|required|max:20',
            'credit_limit' => 'sometimes|required|numeric',
            'status' => 'sometimes|required',
        ]);

        // Prevent setting status to inactive if balance > 0
        if (isset($validated['status']) && $validated['status'] === 'inactive' && $customer->current_balance > 0) {
            return response()->json([
                'message' => 'Lama xiri karo macmiil DEEN lagu leeyahay (Cannot deactivate customer with outstanding debt).'
            ], 422);
        }

        $customer->update($validated);
        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        // Check for outstanding debt
        if ($customer->current_balance > 0) {
            return response()->json([
                'message' => 'Macmiilkan lama masaxi karo sababtoo ah waxaa lagu leeyahay lacag DEEN ah.'
            ], 403);
        }

        // Check for sales history
        $hasSales = \App\Models\Sale::where('customer_id', $customer->customer_id)->exists();
        if ($hasSales) {
            return response()->json([
                'message' => 'Macmiilkan lama masaxi karo sababtoo ah wuxuu leeyahay taariikh iib (Sales History). Fadlan kaliya ka dhig mid aan shaqeynayn (Inactive).'
            ], 403);
        }

        $customer->delete();
        return response()->json(null, 204);
    }
}
