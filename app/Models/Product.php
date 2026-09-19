<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Storefront catalog/home pages cache reads under dynamic per-filter
        // keys (see CatalogController::cacheRemember), so there's no single
        // key to target here — flush the whole cache store instead so admin
        // edits show up immediately rather than waiting out the TTL.
        static::saved(fn () => Cache::flush());
        static::deleted(fn () => Cache::flush());
    }

    protected $fillable = [
        'category_id',
        'brand_id',
        'supplier_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'compare_at_price',
        'cost_price',
        'stock_quantity',
        'is_active',
        'is_featured',
        'image',
        'images',
        'specifications',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'specifications' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock_quantity' => 'integer',
        ];
    }

    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getDisplayCategoryAttribute(): string
    {
        return $this->categoryRelation?->name ?: ucfirst((string) $this->category);
    }

    public function getImageUrlAttribute(): string
    {
        if (! $this->image) {
            return asset('images/product-placeholder.svg');
        }

        if (Str::startsWith($this->image, ['http://', 'https://', '/'])) {
            return $this->image;
        }

        if (Str::startsWith($this->image, 'images/')) {
            return asset($this->image);
        }

        // asset() (not Storage::disk('public')->url()) so this resolves against
        // the actual request host, matching how the rest of the app derives URLs
        // instead of depending on APP_URL (see commit a6d3a8b).
        return asset('storage/' . $this->image);
    }
}
