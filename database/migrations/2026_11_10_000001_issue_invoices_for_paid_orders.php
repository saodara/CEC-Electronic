<?php

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Orders paid before invoices were issued automatically have none, so
     * give each one an invoice dated when it was paid.
     */
    public function up(): void
    {
        Order::query()
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled')
            ->whereDoesntHave('invoice')
            ->lazyById()
            ->each(fn (Order $order) => Invoice::issueFor($order, $order->payment_confirmed_at ?? $order->created_at));
    }

    /**
     * Left in place: these invoices can't be told apart from ones made by hand.
     */
    public function down(): void
    {
    }
};
