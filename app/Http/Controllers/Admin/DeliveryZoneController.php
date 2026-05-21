<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryZoneController extends Controller
{
    public function index(): View
    {
        $zones = DeliveryZone::latest()->paginate(15);

        return view('admin.delivery-zones.index', compact('zones'));
    }

    public function create(): View
    {
        $zone = new DeliveryZone(['is_active' => true, 'estimated_days' => 1]);

        return view('admin.delivery-zones.create', compact('zone'));
    }

    public function store(Request $request): RedirectResponse
    {
        DeliveryZone::create($this->validatedData($request));

        return redirect()->route('admin.delivery-zones.index')->with('status', 'Delivery zone created.');
    }

    public function edit(DeliveryZone $deliveryZone): View
    {
        $zone = $deliveryZone;

        return view('admin.delivery-zones.edit', compact('zone'));
    }

    public function update(Request $request, DeliveryZone $deliveryZone): RedirectResponse
    {
        $deliveryZone->update($this->validatedData($request));

        return redirect()->route('admin.delivery-zones.index')->with('status', 'Delivery zone updated.');
    }

    public function destroy(DeliveryZone $deliveryZone): RedirectResponse
    {
        $deliveryZone->delete();

        return redirect()->route('admin.delivery-zones.index')->with('status', 'Delivery zone deleted.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'delivery_fee' => ['nullable', 'integer', 'min:0'],
            'free_delivery_minimum' => ['nullable', 'integer', 'min:0'],
            'estimated_days' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['delivery_fee' => 0, 'estimated_days' => 1, 'is_active' => false];
    }
}
