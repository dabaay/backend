<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupplierController extends Controller
{
    public function index()
    {
        return response()->json(Supplier::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:100',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:50',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
            'document' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048'
        ]);

        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('suppliers/documents', 'public');
            $validated['document_path'] = $path;
        }

        $supplier = Supplier::create($validated);

        return response()->json($supplier, 201);
    }

    public function show(Supplier $supplier)
    {
        return response()->json($supplier);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'company_name' => 'sometimes|required|string|max:100',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:50',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date',
            'status' => 'sometimes|required|in:active,inactive',
            'notes' => 'nullable|string',
            'document' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048'
        ]);

        if ($request->hasFile('document')) {
            // Delete old document if exists
            if ($supplier->document_path) {
                Storage::disk('public')->delete($supplier->document_path);
            }
            $path = $request->file('document')->store('suppliers/documents', 'public');
            $validated['document_path'] = $path;
        }

        $supplier->update($validated);

        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        // Check for purchase history
        $hasPurchases = \App\Models\Purchase::where('supplier_id', $supplier->supplier_id)->exists();
        if ($hasPurchases) {
            return response()->json([
                'message' => 'Lama masaxi karo alaab-qeybiyahan (Supplier) sababtoo ah wuxuu leeyahay taariikh iibsi (Purchase History). Fadlan kaliya ka dhig mid aan shaqeynayn (Inactive).'
            ], 403);
        }

        if ($supplier->document_path) {
            Storage::disk('public')->delete($supplier->document_path);
        }
        $supplier->delete();
        return response()->json(null, 204);
    }

    public function uploadDocument(Request $request, Supplier $supplier)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,png|max:2048'
        ]);

        if ($supplier->document_path) {
            Storage::disk('public')->delete($supplier->document_path);
        }

        $path = $request->file('document')->store('suppliers/documents', 'public');
        $supplier->update(['document_path' => $path]);

        return response()->json([
            'message' => 'Warqadda si guul leh ayaa loo soo raray.',
            'document_path' => $path
        ]);
    }
}
