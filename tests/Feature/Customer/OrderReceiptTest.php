<?php

namespace Tests\Feature\Customer;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function paidOrderFor(User $user): Order
    {
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'paid',
            'payment_confirmed_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Dell Latitude 5440',
            'sku' => 'DL-5440',
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
        ]);

        return $order;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $order = Order::factory()->create(['payment_status' => 'paid']);

        $this->get(route('account.orders.receipt', $order))
            ->assertRedirect(route('customer.login'));
    }

    public function test_owner_can_download_receipt_for_paid_order(): void
    {
        $user = User::factory()->create();
        $order = $this->paidOrderFor($user);

        $response = $this->actingAs($user)->get(route('account.orders.receipt', $order));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'receipt-'.$order->order_number.'.pdf',
            $response->headers->get('content-disposition')
        );
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_receipt_is_not_available_for_unpaid_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'payment_status' => 'unpaid']);

        $this->actingAs($user)
            ->get(route('account.orders.receipt', $order))
            ->assertNotFound();
    }

    public function test_other_customers_cannot_download_receipt(): void
    {
        $owner = User::factory()->create();
        $order = $this->paidOrderFor($owner);

        $this->actingAs(User::factory()->create())
            ->get(route('account.orders.receipt', $order))
            ->assertForbidden();
    }

    public function test_order_page_links_to_receipt_only_when_paid(): void
    {
        $user = User::factory()->create();
        $paid = $this->paidOrderFor($user);
        $unpaid = Order::factory()->create(['user_id' => $user->id, 'payment_status' => 'unpaid']);

        $this->actingAs($user)->get(route('account.orders.show', $paid))
            ->assertSee(route('account.orders.receipt', $paid), false);

        $this->actingAs($user)->get(route('account.orders.show', $unpaid))
            ->assertDontSee(route('account.orders.receipt', $unpaid), false);
    }

    public function test_success_page_links_to_receipt_when_paid(): void
    {
        $user = User::factory()->create();
        $order = $this->paidOrderFor($user);

        $this->actingAs($user)->get(route('checkout.success', $order))
            ->assertSee(route('account.orders.receipt', $order), false);
    }

    public function test_admin_can_download_receipt_for_paid_order(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->paidOrderFor(User::factory()->create());

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.orders.receipt', $order));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
