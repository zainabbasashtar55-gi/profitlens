<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Models\Product;
use App\Services\PlanEnforcer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query();
        if ($search = $request->query('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }

        return view('tenant.products.index', [
            'products' => $query->orderBy('name')->paginate(20)->withQueryString(),
            'search'   => $search,
        ]);
    }

    public function create(): View
    {
        return view('tenant.products.form', ['product' => new Product(['active' => true])]);
    }

    public function store(StoreProductRequest $request, PlanEnforcer $plan): RedirectResponse
    {
        if (! $plan->canAdd('products')) {
            $check = $plan->check('products');
            return back()->withErrors([
                'plan' => "Plan limit reached ({$check['current']}/{$check['limit']} products). Upgrade to add more.",
            ])->withInput();
        }

        Product::create($request->validated());

        return redirect()->route('products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        return view('tenant.products.form', ['product' => $product]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()->route('products.index')->with('status', 'Product updated.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);
        $product->delete();

        return redirect()->route('products.index')->with('status', 'Product deleted.');
    }
}
