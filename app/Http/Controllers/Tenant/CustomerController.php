<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Customer::query()->withCount('sales');

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        return view('tenant.customers.index', [
            'customers' => $query->orderByDesc('created_at')->paginate(20)->withQueryString(),
            'search'    => $search,
        ]);
    }

    public function create(): View
    {
        return view('tenant.customers.form', ['customer' => new Customer]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::create([...$request->validated(), 'created_by' => $request->user()->id]);

        return redirect()->route('customers.index')->with('status', 'Customer created.');
    }

    public function edit(Customer $customer): View
    {
        return view('tenant.customers.form', ['customer' => $customer]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('customers.index')->with('status', 'Customer updated.');
    }

    public function destroy(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);
        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Customer deleted.');
    }
}
