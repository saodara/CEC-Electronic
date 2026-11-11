<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sample records so the admin pages have something to show. Everything is labelled
 * DEMO and removable, and nothing here changes real stock or product costs.
 */
class DemoData extends Command
{
    private const MARK = '[DEMO]';

    protected $signature = 'demo:data {action : seed or remove}';

    protected $description = 'Add or remove clearly labelled sample records for the admin pages';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'seed' => $this->seed(),
            'remove' => $this->remove(),
            default => $this->invalidAction(),
        };
    }

    private function invalidAction(): int
    {
        $this->error('The action must be seed or remove.');

        return self::FAILURE;
    }

    private function seed(): int
    {
        if (PurchaseOrder::query()->where('po_number', 'like', 'DEMO-%')->exists()) {
            $this->warn('Sample records already exist. Nothing was added.');

            return self::SUCCESS;
        }

        $supplier = Supplier::query()->whereHas('products')->orderBy('id')->first();

        if (! $supplier) {
            $this->error('Sample records need at least one supplier with products.');

            return self::FAILURE;
        }

        $today = now(config('app.shop_timezone'))->startOfDay();
        $monthStart = $today->copy()->startOfMonth();
        $day = fn (int $days) => $monthStart->copy()->addDays($days)->min($today)->toDateString();

        DB::transaction(function () use ($supplier, $today, $day) {
            $products = Product::query()->where('supplier_id', $supplier->id)->orderBy('id')->take(6)->get();

            $orders = [
                ['DEMO-PO-1001', 'received', $day(1), 'paid'],
                ['DEMO-PO-1002', 'received', $day(3), 'unpaid'],
                ['DEMO-PO-1003', 'ordered', null, null],
                ['DEMO-PO-1004', 'draft', null, null],
                ['DEMO-PO-1005', 'cancelled', null, null],
            ];

            foreach ($orders as $index => [$number, $status, $receivedOn, $billStatus]) {
                $purchaseOrder = PurchaseOrder::create([
                    'po_number' => $number,
                    'supplier_id' => $supplier->id,
                    'status' => $status,
                    'expected_date' => $today->copy()->addDays(($index + 1) * 2)->toDateString(),
                    'received_date' => $receivedOn,
                    'notes' => self::MARK.' Sample purchase order. It did not change stock or product costs.',
                ]);

                $total = 0;

                foreach ($products->slice($index % 3, 2) as $product) {
                    $quantity = 2 + $index;
                    $unitCost = round((float) $product->price * 0.7, 2);
                    $lineTotal = round($quantity * $unitCost, 2);
                    $total += $lineTotal;

                    $purchaseOrder->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_cost' => $unitCost,
                        'line_total' => $lineTotal,
                    ]);
                }

                $purchaseOrder->update(['subtotal' => $total, 'grand_total' => $total]);

                if ($billStatus) {
                    Bill::create([
                        'bill_number' => 'DEMO-BILL-'.(1001 + $index),
                        'supplier_id' => $supplier->id,
                        'purchase_order_id' => $purchaseOrder->id,
                        'supplier_reference' => self::MARK.' Sample',
                        'bill_date' => $receivedOn,
                        'due_date' => $today->copy()->addDays(30 - ($index * 40))->toDateString(),
                        'total' => $total,
                        'status' => $billStatus,
                        'paid_on' => $billStatus === 'paid' ? $day(4) : null,
                    ]);
                }
            }

            Order::query()->where('status', '!=', 'cancelled')->whereDoesntHave('invoice')->orderBy('id')->get()
                ->each(fn (Order $order, int $index) => Invoice::create([
                    'invoice_number' => 'DEMO-INV-'.(1001 + $index),
                    'order_id' => $order->id,
                    'issued_on' => $order->created_at->copy()->setTimezone(config('app.shop_timezone'))->toDateString(),
                ]));

            foreach ($products->take(3)->values() as $index => $product) {
                [$change, $reason] = [[5, 'Stock count correction'], [-1, 'Damaged'], [1, 'Customer return']][$index];

                StockAdjustment::create([
                    'product_id' => $product->id,
                    'product_name' => self::MARK.' '.$product->name,
                    'quantity_change' => $change,
                    'stock_after' => $product->stock_quantity,
                    'reason' => $reason,
                    'notes' => 'Sample record. Real stock was not changed.',
                ]);
            }

            $expenses = [['Shop rent', 'Rent', 120], ['Staff salary', 'Salaries', 90], ['Electricity bill', 'Utilities', 35], ['Facebook ads', 'Marketing', 25]];

            foreach ($expenses as $index => [$title, $category, $amount]) {
                Expense::create([
                    'title' => self::MARK.' '.$title,
                    'category' => $category,
                    'amount' => $amount,
                    'spent_on' => $day($index),
                    'notes' => 'Sample record.',
                ]);
            }
        });

        $this->info('Sample records added. Remove them with: php artisan demo:data remove');

        return self::SUCCESS;
    }

    private function remove(): int
    {
        $removed = DB::transaction(fn () => [
            'bills' => Bill::query()->where('bill_number', 'like', 'DEMO-%')->delete(),
            'purchase orders' => PurchaseOrder::query()->where('po_number', 'like', 'DEMO-%')->delete(),
            'invoices' => Invoice::query()->where('invoice_number', 'like', 'DEMO-%')->delete(),
            'stock adjustments' => StockAdjustment::query()->where('product_name', 'like', self::MARK.'%')->delete(),
            'expenses' => Expense::query()->where('title', 'like', self::MARK.'%')->delete(),
        ]);

        foreach ($removed as $type => $count) {
            $this->line("Removed {$count} {$type}.");
        }

        return self::SUCCESS;
    }
}
