<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerRepositoryInterface $customerRepository,
        protected CustomerService $customerService
    ) {}

    public function index(Request $request)
    {
        $customers = $this->customerRepository->getPaginatedCustomers(
            $request->input('per_page', 10),
            $request->input('search'),
            $request->input('status'),
            $request->input('sort', 'created_at'),
            $request->input('direction', 'desc')
        );

        return Inertia::render('customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'status', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function show($id)
    {
        $customer = $this->customerService->getCustomerById($id);
        try {
            return Inertia::render('customers/Show', [
                'customer' => $customer,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('customers.index')->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $customer = $this->customerService->getCustomerById($id);
        try {
            return Inertia::render('customers/Edit', [
                'customer' => $customer,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('customers.index')->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $customer = $this->customerService->getCustomerById($id);
        } catch (\Exception $e) {
            return redirect()->route('customers.index')->with('error', $e->getMessage());
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($customer->user_id)],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'is_active' => 'boolean',
        ]);

        try {
            $this->customerService->updateCustomer($id, $validated);
            return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update customer: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $this->customerService->deleteCustomer($id);
            return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete customer: ' . $e->getMessage());
        }
    }
}
