<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category_id', 'name', 'price', 'image', 'status'])]
class Menu extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Baris resep (bahan + takaran per porsi). */
    public function recipe(): HasMany
    {
        return $this->hasMany(MenuIngredient::class);
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'menu_ingredients')->withPivot('qty_needed');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('menus.status', self::STATUS_ACTIVE);
    }

    /**
     * Tambahkan porsi_tersedia dan is_available dari view v_menu_availability
     * (dipakai daftar menu di POS dan form pesanan waitress).
     */
    public function scopeWithAvailability(Builder $query): Builder
    {
        return $query
            ->leftJoin('v_menu_availability as va', 'va.menu_id', '=', 'menus.id')
            ->select('menus.*', 'va.porsi_tersedia', 'va.is_available');
    }
}
