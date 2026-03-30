<?php

namespace App\Http\Controllers;

use App\Models\VatCustomer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VatCustomerController extends Controller
{
    public function index()
    {
        $customers = VatCustomer::query()
            ->orderByDesc('created_at')
            ->get();

        return view('vat-customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:50', 'unique:vat_customers,vat_number'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        VatCustomer::create($validated);

        return redirect()->route('vat-customers.index')->with('success', 'VAT customer registered successfully.');
    }

    public function update(Request $request, VatCustomer $vatCustomer)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'vat_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('vat_customers', 'vat_number')->ignore($vatCustomer->id),
            ],
            'telephone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $vatCustomer->update($validated);

        return redirect()->route('vat-customers.index')->with('success', 'VAT customer updated successfully.');
    }
}
