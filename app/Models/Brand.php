<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // See Product::booted() — same reasoning: no single cache key to
        // target for the storefront's dynamic catalog cache keys.
        static::saved(fn () => Cache::flush());
        static::deleted(fn () => Cache::flush());
    }

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getInitialsAttribute(): string
    {
        return Str::of($this->name)->substr(0, 2)->upper()->toString();
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        if (Str::startsWith($this->logo, ['http://', 'https://', '/'])) {
            return $this->logo;
        }

        if (Str::startsWith($this->logo, 'images/')) {
            return asset($this->logo);
        }

        // asset() rather than Storage::url() — see Category::getImageUrlAttribute().
        return asset('storage/' . $this->logo);
    }
}
