<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\BakongService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Keeps confirming Bakong KHQR payments in the background so a customer
 * closing their browser tab (or an admin never checking back) doesn't leave
 * a paid order stuck as "unpaid" forever — the live page polling only covers
 * the first ~10 minutes after checkout.
 */
class CheckPendingBakongPayments extends Command
{
    protected $signature = 'bakong:check-pending {--limit=5 : Maximum number of orders to check this run}';

    protected $description = 'Check pending Bakong KHQR orders against the Bakong transaction API and mark them paid once confirmed.';

    public function handle(BakongService $bakong): int
    {
        $limit = max(1, (int) $this->option('limit'));

        // Newest first: a customer waiting right now matters more than an
        // hours-old abandoned cart, and without this, a backlog of stale
        // unpaid orders would fill every run's limit and starve new ones out
        // forever. A 2-hour window also means we stop spending budget on
        // carts that are almost certainly abandoned rather than just slow.
        $orders = Order::query()
            ->where('payment_method', 'bakong')
            ->where('payment_status', 'unpaid')
            ->whereNotNull('bakong_qr_md5')
            ->where('created_at', '>=', now()->subHours(2))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $checked = 0;
        $confirmed = 0;

        foreach ($orders as $order) {
            // Without this, every unpaid order in the 2-hour window gets
            // re-checked on every single 5-minute run — a handful of stale
            // abandoned carts can then burn most of the shared daily Bakong
            // budget by themselves, starving checks for orders customers are
            // actively paying right now. Space job-driven rechecks per order
            // out to once every 20 minutes instead (live page polling already
            // covers the minutes right after checkout much more tightly).
            if (! Cache::add("bakong-job-check:{$order->id}", true, 1200)) {
                continue;
            }

            $checked++;
            $transaction = $bakong->checkTransactionByMd5($order->bakong_qr_md5);

            if ($transaction !== null) {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_confirmed_at' => now(),
                ]);
                $confirmed++;
            }
        }

        $this->info("Checked {$checked} of {$orders->count()} pending Bakong order(s), confirmed {$confirmed}.");

        return self::SUCCESS;
    }
}
