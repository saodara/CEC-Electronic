<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryProviderController extends Controller
{
    public function index(): View
    {
        $providers = DeliveryProvider::latest()->paginate(15);

        return view('admin.delivery-providers.index', compact('providers'));
    }

    public function create(): View
    {
        $provider = new DeliveryProvider(['is_active' => true]);

        return view('admin.delivery-providers.create', compact('provider'));
    }

    public function store(Request $request): RedirectResponse
    {
        DeliveryProvider::create($this->validatedData($request));

        return redirect()->route('admin.delivery-providers.index')->with('status', 'Delivery provider created.');
    }

    public function edit(DeliveryProvider $deliveryProvider): View
    {
        $provider = $deliveryProvider;

        return view('admin.delivery-providers.edit', compact('provider'));
    }

    public function update(Request $request, DeliveryProvider $deliveryProvider): RedirectResponse
    {
        $deliveryProvider->update($this->validatedData($request));

        return redirect()->route('admin.delivery-providers.index')->with('status', 'Delivery provider updated.');
    }

    public function destroy(DeliveryProvider $deliveryProvider): RedirectResponse
    {
        $deliveryProvider->delete();

        return redirect()->route('admin.delivery-providers.index')->with('status', 'Delivery provider deleted.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:255'],
            'base_fee' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]) + ['base_fee' => 0, 'is_active' => false];
    }
}
