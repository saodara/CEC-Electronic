<?php

namespace Database\Seeders;

use App\Models\DeliveryProvider;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierDeliverySeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = collect([
            ['name' => 'CEC Distribution', 'company_name' => 'CEC Electronic Co., Ltd', 'phone' => '012 220 152', 'email' => 'supply@cecelectronic.test', 'payment_terms' => 'Net 15'],
            ['name' => 'Cambodia Tech Import', 'company_name' => 'Cambodia Tech Import', 'phone' => '093 456 747', 'email' => 'orders@cti.test', 'payment_terms' => 'Prepaid'],
            ['name' => 'Asia Gadget Wholesale', 'company_name' => 'Asia Gadget Wholesale', 'phone' => '010 889 900', 'email' => 'sales@agw.test', 'payment_terms' => 'COD'],
        ])->map(fn (array $supplier) => Supplier::updateOrCreate(['name' => $supplier['name']], $supplier + ['is_active' => true]));

        Product::query()->whereNull('supplier_id')->get()->each(function (Product $product, int $index) use ($suppliers) {
            $product->update(['supplier_id' => $suppliers[$index % $suppliers->count()]->id]);
        });

        foreach ([
            ['name' => 'Phnom Penh Express', 'city' => 'Phnom Penh', 'province' => 'Phnom Penh', 'delivery_fee' => 2, 'free_delivery_minimum' => 500, 'estimated_days' => 1],
            ['name' => 'Provincial Standard', 'city' => null, 'province' => null, 'delivery_fee' => 5, 'free_delivery_minimum' => 800, 'estimated_days' => 3],
            ['name' => 'Same Day Central', 'city' => 'Phnom Penh', 'province' => 'Phnom Penh', 'delivery_fee' => 4, 'free_delivery_minimum' => 1000, 'estimated_days' => 1],
        ] as $zone) {
            DeliveryZone::updateOrCreate(['name' => $zone['name']], $zone + ['is_active' => true]);
        }

        foreach ([
            ['name' => 'CEC Rider Team', 'phone' => '012 220 152', 'email' => 'delivery@cecelectronic.test', 'base_fee' => 2],
            ['name' => 'Virak Buntham Express', 'phone' => '023 999 999', 'email' => 'support@vbt.test', 'base_fee' => 5],
            ['name' => 'J&T Express Cambodia', 'phone' => '1800 200 999', 'email' => 'support@jnt.test', 'base_fee' => 4],
        ] as $provider) {
            DeliveryProvider::updateOrCreate(['name' => $provider['name']], $provider + ['is_active' => true]);
        }
    }
}
