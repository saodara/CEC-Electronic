<?php

namespace App\Models;

use App\Services\OrderStock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updated(function (Order $order) {
            if ($order->wasChanged(['status', 'payment_status'])) {
                app(OrderStock::class)->sync($order);
            }

            if ($order->wasChanged('payment_status') && $order->payment_status === 'paid' && $order->status !== 'cancelled') {
                Invoice::issueFor($order);
            }
        });
    }

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'payment_status',
        'payment_confirmed_at',
        'admin_payment_seen_at',
        'payment_method',
        'bakong_session_id',
        'bakong_checkout_url',
        'bakong_qr_string',
        'bakong_qr_md5',
        'bakong_qr_expires_at',
        'shipping_method',
        'delivery_zone_id',
        'delivery_provider_id',
        'tracking_number',
        'shipped_at',
        'delivered_at',
        'subtotal',
        'shipping_total',
        'discount_total',
        'grand_total',
        'shipping_address',
        'notes',
        'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'subtotal' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'placed_at' => 'datetime',
            'payment_confirmed_at' => 'datetime',
            'bakong_qr_expires_at' => 'datetime',
            'admin_payment_seen_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'stock_deducted_at' => 'datetime',
        ];
    }

    public function bakongQrExpired(): bool
    {
        return $this->bakong_qr_expires_at !== null && $this->bakong_qr_expires_at->isPast();
    }

    /**
     * Plain-language explanation of the order's status for customers. Paying
     * does not change `status`, so a pending order's meaning depends on its
     * payment too.
     */
    public function statusMeaning(): string
    {
        return match ($this->status) {
            'pending' => match (true) {
                $this->payment_status === 'paid' => 'Payment received. Your order is waiting for delivery.',
                $this->payment_status === 'failed' => "Your payment didn't go through. Please call 012 220 152 for help.",
                $this->payment_status === 'refunded' => 'Your payment was refunded.',
                $this->payment_method === 'cash_on_delivery' => "We got your order and will confirm it soon. You'll pay on delivery.",
                default => 'Waiting for your payment.',
            },
            'processing' => "We're preparing your order.",
            'shipped' => 'Your order is on the way.',
            'completed' => $this->payment_status === 'refunded'
                ? 'Your payment for this order was refunded.'
                : 'You have received your product. Thank you for shopping with us!',
            'cancelled' => $this->payment_status === 'refunded'
                ? 'This order was cancelled and your payment refunded.'
                : 'This order was cancelled.',
            default => '',
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function deliveryProvider(): BelongsTo
    {
        return $this->belongsTo(DeliveryProvider::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
