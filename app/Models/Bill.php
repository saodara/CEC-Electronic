<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
{
    protected $fillable = [
        'bill_number', 'supplier_id', 'purchase_order_id', 'supplier_reference',
        'bill_date', 'due_date', 'total', 'status', 'paid_on',
    ];

    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'due_date' => 'date',
            'paid_on' => 'date',
            'total' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'unpaid'
            && $this->due_date->toDateString() < now(config('app.shop_timezone'))->toDateString();
    }
}
