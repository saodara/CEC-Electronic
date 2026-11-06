<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_matches_the_requested_list_in_order(): void
    {
        $this->actingAsAdmin();

        $html = $this->get(route('admin.dashboard'))->assertOk()->getContent();
        $nav = substr($html, strpos($html, '<nav class="nav">'), strpos($html, '</nav>') - strpos($html, '<nav class="nav">'));

        preg_match_all('#</span>([^<]+)</a>|<a href="[^"]+"(?: class="[^"]*")?>([^<]+)</a>|</span>(Report)<#', $nav, $matches);
        $items = array_map(
            fn ($values) => html_entity_decode(trim(implode('', $values))),
            array_map(null, $matches[1], $matches[2], $matches[3])
        );

        $this->assertSame([
            'Dashboard', 'Product', 'Category',
            'Supplier', 'Purchase Order', 'Bill',
            'Customer', 'Sale Order', 'Invoice',
            'Expenses',
            'Report', 'Profit & Loss', 'Product Revenue', 'Customer', 'Supplier',
        ], $items);
        $this->assertStringContainsString('<span class="nav-label">System</span>', $nav);
        $this->assertSame(4, substr_count($nav, 'nav-sep'));
    }

    public function test_every_menu_page_opens(): void
    {
        $this->actingAsAdmin();

        $routes = [
            route('admin.dashboard'),
            route('admin.products.index'),
            route('admin.categories.index'),
            route('admin.suppliers.index'),
            route('admin.purchase-orders.index'),
            route('admin.bills.index'),
            route('admin.customers.index'),
            route('admin.orders.index'),
            route('admin.invoices.index'),
            route('admin.expenses.index'),
            route('admin.reports.profit-loss'),
            route('admin.reports.products'),
            route('admin.reports.customers'),
            route('admin.reports.suppliers'),
        ];

        $menu = $this->get(route('admin.dashboard'))->assertOk();

        foreach ($routes as $url) {
            $menu->assertSee('href="'.$url.'"', false);
            $this->get($url)->assertOk();
        }
    }

    public function test_pages_outside_the_menu_are_reached_from_related_pages(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.products.index'))->assertOk()->assertSee('href="'.route('admin.brands.index').'"', false);
        $this->get(route('admin.orders.index'))->assertOk()->assertSee('href="'.route('admin.delivery-zones.index').'"', false);

        foreach (['profit-loss', 'products', 'customers', 'suppliers'] as $report) {
            $page = $this->get(route('admin.reports.'.$report))->assertOk();

            foreach (['day', 'week', 'month', 'year'] as $period) {
                $page->assertSee('href="'.route('admin.reports.orders', ['period' => $period]).'"', false);
            }
        }

        $this->get(route('admin.reports.orders', ['period' => 'week']))->assertOk()
            ->assertSee('href="'.route('admin.reports.profit-loss').'"', false);
    }

    public function test_brand_and_delivery_pages_highlight_their_parent_menu_item(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.brands.index'))
            ->assertSee('<a href="'.route('admin.products.index').'" class="active">', false);
        $this->get(route('admin.delivery-zones.index'))
            ->assertSee('<a href="'.route('admin.orders.index').'" class="active">', false);
    }

    public function test_current_page_is_highlighted(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.orders.index'))
            ->assertSee('<a href="'.route('admin.orders.index').'" class="active">', false);
    }
}
