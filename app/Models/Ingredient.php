<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * stock_qty sengaja tidak ada di $fillable: stok hanya berubah lewat StockMovement
 * (trigger fn_apply_stock_movement), supaya setiap perubahan tercatat.
 */
#[Fillable(['name', 'unit', 'min_stock', 'cost_per_unit', 'is_active'])]
class Ingredient extends Model
{
    protected function casts(): array
    {
        return [
            'stock_qty' => 'decimal:2',
            'min_stock' => 'decimal:2',
            'cost_per_unit' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'menu_ingredients')->withPivot('qty_needed');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Stok habis atau menipis (sama dengan status di view v_ingredient_stock). */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_qty', '<=', 'min_stock');
    }
}
