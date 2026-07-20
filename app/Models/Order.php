<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
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
            'placed_at' => 'datetime',
            'payment_confirmed_at' => 'datetime',
            'admin_payment_seen_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
