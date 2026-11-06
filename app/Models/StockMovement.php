<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pergerakan stok. Menyimpan baris baru otomatis mengubah ingredients.stock_qty (trigger).
 * `in` dan `out` memakai qty positif; `adjustment` memakai selisih bertanda (+/-).
 */
#[Fillable(['ingredient_id', 'user_id', 'transaction_id', 'type', 'qty', 'notes'])]
class StockMovement extends Model
{
    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';

    // Tabel hanya punya created_at.
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['qty' => 'decimal:2'];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
