<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Invoice extends Model
{
    protected $fillable = ['invoice_number', 'order_id', 'issued_on'];

    /**
     * The order's invoice, created if it doesn't have one yet.
     */
    public static function issueFor(Order $order, ?DateTimeInterface $issuedAt = null): self
    {
        $issuedOn = Carbon::instance($issuedAt ?? now())->setTimezone(config('app.shop_timezone'));

        return static::query()->firstOrCreate(['order_id' => $order->id], [
            'invoice_number' => static::uniqueNumber($issuedOn),
            'issued_on' => $issuedOn->toDateString(),
        ]);
    }

    private static function uniqueNumber(Carbon $issuedOn): string
    {
        do {
            $number = 'INV-'.$issuedOn->format('Ymd').'-'.random_int(1000, 9999);
        } while (static::query()->where('invoice_number', $number)->exists());

        return $number;
    }

    protected function casts(): array
    {
        return ['issued_on' => 'date'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
