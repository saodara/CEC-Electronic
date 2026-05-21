<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => env('ADMIN_EMAIL', 'admin@cecelectronic.co')], [
            'name' => 'CEC Admin',
            'password' => env('ADMIN_PASSWORD', 'change-this-password'),
            'is_admin' => true,
        ]);

        // Seed products
        $this->call([
            ProductSeeder::class,
            SupplierDeliverySeeder::class,
        ]);
    }
}
