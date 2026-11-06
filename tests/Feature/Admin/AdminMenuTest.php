<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_shows_the_groups_in_order_with_working_links(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['System', 'Dashboard', 'Product', 'Category', 'Brand',
                'Purchasing', 'Supplier', 'Purchase Order', 'Bill',
                'Sales', 'Customer', 'Sale Order', 'Invoice', 'Delivery',
                'Stock &amp; Expenses', 'Stock Adjustment (+/-)', 'Expenses',
                'Reports', 'Report', 'Daily', 'Weekly', 'Monthly', 'Yearly',
                'Profit &amp; Loss', 'Product Revenue', 'Customer', 'Supplier',
                'Storefront', 'View Store'], false);
    }

    public function test_every_existing_page_is_still_reachable_from_the_menu(): void
    {
        $this->actingAsAdmin();

        $routes = [
            route('admin.dashboard'),
            route('admin.products.index'),
            route('admin.categories.index'),
            route('admin.brands.index'),
            route('admin.suppliers.index'),
            route('admin.customers.index'),
            route('admin.orders.index'),
            route('admin.delivery-zones.index'),
            route('admin.purchase-orders.index'),
            route('admin.bills.index'),
            route('admin.invoices.index'),
            route('admin.stock-adjustments.index'),
            route('admin.expenses.index'),
            route('admin.reports.profit-loss'),
            route('admin.reports.products'),
            route('admin.reports.customers'),
            route('admin.reports.suppliers'),
            route('admin.reports.orders', ['period' => 'day']),
            route('admin.reports.orders', ['period' => 'year']),
        ];

        $menu = $this->get(route('admin.dashboard'))->assertOk();
        $menu->assertDontSee('<small>Soon</small>', false);

        foreach ($routes as $url) {
            $menu->assertSee('href="'.$url.'"', false);
            $this->get($url)->assertOk();
        }
    }

    public function test_current_page_is_highlighted(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.orders.index'))
            ->assertSee('<a href="'.route('admin.orders.index').'" class="active">', false);
    }
}
