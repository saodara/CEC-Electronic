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
        return $this->pdf($order)->download($this->filename($order));
    }

    /**
     * Same receipt, opened in the browser instead of saved.
     */
    public function stream(Order $order): Response
    {
        return $this->pdf($order)->stream($this->filename($order));
    }

    private function pdf(Order $order)
    {
        abort_unless($order->payment_status === 'paid', 404);

        $order->loadMissing('items', 'deliveryProvider');

        return Pdf::loadView('receipts.order', compact('order'))->setPaper('a4');
    }

    private function filename(Order $order): string
    {
        return 'receipt-'.$order->order_number.'.pdf';
    }
}
