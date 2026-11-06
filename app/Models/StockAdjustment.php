<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    public const REASONS = ['Stock count correction', 'Damaged', 'Lost or stolen', 'Customer return', 'Other'];

    protected $fillable = ['product_id', 'product_name', 'quantity_change', 'stock_after', 'reason', 'notes', 'user_id'];

    protected function casts(): array
    {
        return ['quantity_change' => 'integer', 'stock_after' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
