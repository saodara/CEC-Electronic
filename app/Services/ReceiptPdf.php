<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReceiptPdf
{
    /**
     * A receipt only exists once payment is confirmed; unpaid orders 404.
     */
    public function download(Order $order): Response
    {
        abort_unless($order->payment_status === 'paid', 404);

        $order->loadMissing('items', 'deliveryProvider');

        return Pdf::loadView('receipts.order', compact('order'))
            ->setPaper('a4')
            ->download('receipt-'.$order->order_number.'.pdf');
    }
}
