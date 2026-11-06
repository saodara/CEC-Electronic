<?php

namespace Tests\Feature\Admin;

use App\Models\Bill;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function draft(Product $product, int $quantity = 5, float $unitCost = 10.5): PurchaseOrder
    {
        $this->post(route('admin.purchase-orders.store'), [
            'supplier_id' => Supplier::factory()->create()->id,
            'items' => [['product_id' => $product->id, 'quantity' => $quantity, 'unit_cost' => $unitCost]],
        ])->assertSessionHasNoErrors();

        return PurchaseOrder::latest('id')->firstOrFail();
    }

    public function test_guest_cannot_open_purchase_pages(): void
    {
        $this->get(route('admin.purchase-orders.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.bills.index'))->assertRedirect(route('admin.login'));
    }

    public function test_full_flow_from_draft_to_paid_bill(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create(['stock_quantity' => 3, 'cost_price' => 8]);

        $order = $this->draft($product);
        $this->assertSame('draft', $order->status);
        $this->assertSame('52.50', $order->grand_total);
        $this->get(route('admin.purchase-orders.show', $order))->assertOk()->assertSee('Mark as ordered');
        $this->assertSame(3, $product->fresh()->stock_quantity);

        $this->post(route('admin.purchase-orders.order', $order))->assertSessionHasNoErrors();
        $this->assertSame(3, $product->fresh()->stock_quantity);

        $this->post(route('admin.purchase-orders.receive', $order))->assertSessionHasNoErrors();
        $product->refresh();
        $this->assertSame(8, $product->stock_quantity);
        $this->assertSame('10.50', $product->cost_price);
        $this->assertSame('received', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->received_date);

        $this->post(route('admin.purchase-orders.bill', $order))->assertRedirect();
        $bill = Bill::firstOrFail();
        $this->assertSame('52.50', $bill->total);
        $this->assertSame('unpaid', $bill->status);
        $this->get(route('admin.bills.show', $bill))->assertOk();
        $this->get(route('admin.bills.index'))->assertOk()->assertSee('$52.50');

        $this->post(route('admin.bills.pay', $bill), ['paid_on' => '2026-11-06'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $bill->fresh()->status);
    }

    public function test_receiving_twice_only_adds_stock_once(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create(['stock_quantity' => 0]);
        $order = $this->draft($product);
        $this->post(route('admin.purchase-orders.order', $order));

        $this->post(route('admin.purchase-orders.receive', $order));
        $this->post(route('admin.purchase-orders.receive', $order))->assertSessionHasErrors('purchase_order');

        $this->assertSame(5, $product->fresh()->stock_quantity);
    }

    public function test_draft_or_cancelled_orders_cannot_be_received(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create(['stock_quantity' => 0]);
        $order = $this->draft($product);

        $this->post(route('admin.purchase-orders.receive', $order))->assertSessionHasErrors('purchase_order');

        $this->post(route('admin.purchase-orders.cancel', $order))->assertSessionHasNoErrors();
        $this->post(route('admin.purchase-orders.order', $order))->assertSessionHasErrors('purchase_order');
        $this->post(route('admin.purchase-orders.receive', $order))->assertSessionHasErrors('purchase_order');

        $this->assertSame(0, $product->fresh()->stock_quantity);
    }

    public function test_only_drafts_can_be_edited_or_deleted(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();
        $order = $this->draft($product);

        $this->get(route('admin.purchase-orders.edit', $order))->assertOk();
        $this->put(route('admin.purchase-orders.update', $order), [
            'supplier_id' => $order->supplier_id,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_cost' => 4]],
        ])->assertSessionHasNoErrors();
        $this->assertSame('8.00', $order->fresh()->grand_total);
        $this->assertSame(1, $order->items()->count());

        $this->post(route('admin.purchase-orders.order', $order));

        $this->get(route('admin.purchase-orders.edit', $order))->assertRedirect(route('admin.purchase-orders.show', $order));
        $this->delete(route('admin.purchase-orders.destroy', $order))->assertSessionHasErrors('purchase_order');
        $this->assertNotNull($order->fresh());
    }

    public function test_order_needs_a_supplier_and_valid_items(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.purchase-orders.store'), ['items' => []])
            ->assertSessionHasErrors(['supplier_id', 'items']);

        $this->post(route('admin.purchase-orders.store'), [
            'supplier_id' => Supplier::factory()->create()->id,
            'items' => [['product_id' => 999, 'quantity' => 0, 'unit_cost' => -1]],
        ])->assertSessionHasErrors(['items.0.product_id', 'items.0.quantity', 'items.0.unit_cost']);

        $this->assertSame(0, PurchaseOrder::count());
    }

    public function test_bill_needs_received_goods_and_each_order_gets_one(): void
    {
        $this->actingAsAdmin();
        $order = $this->draft(Product::factory()->create());

        $this->post(route('admin.purchase-orders.bill', $order))->assertSessionHasErrors('purchase_order');
        $this->assertSame(0, Bill::count());

        $this->post(route('admin.purchase-orders.order', $order));
        $this->post(route('admin.purchase-orders.receive', $order));
        $this->post(route('admin.purchase-orders.bill', $order));
        $this->post(route('admin.purchase-orders.bill', $order));

        $this->assertSame(1, Bill::count());
    }

    public function test_paid_bill_cannot_be_deleted_until_marked_unpaid(): void
    {
        $this->actingAsAdmin();
        $order = $this->draft(Product::factory()->create());
        $this->post(route('admin.purchase-orders.order', $order));
        $this->post(route('admin.purchase-orders.receive', $order));
        $this->post(route('admin.purchase-orders.bill', $order));
        $bill = Bill::firstOrFail();
        $this->post(route('admin.bills.pay', $bill), ['paid_on' => '2026-11-06']);

        $this->delete(route('admin.bills.destroy', $bill))->assertSessionHasErrors('bill');
        $this->assertNotNull($bill->fresh());

        $this->post(route('admin.bills.unpay', $bill));
        $this->assertNull($bill->fresh()->paid_on);
        $this->delete(route('admin.bills.destroy', $bill))->assertSessionHasNoErrors();
        $this->assertNull($bill->fresh());
    }

    public function test_supplier_with_purchase_orders_cannot_be_deleted(): void
    {
        $this->actingAsAdmin();
        $order = $this->draft(Product::factory()->create());

        $this->from(route('admin.suppliers.index'))
            ->followingRedirects()
            ->delete(route('admin.suppliers.destroy', $order->supplier_id))
            ->assertOk()
            ->assertSee('can&#039;t be deleted', false);

        $this->assertNotNull($order->fresh());
    }
}
