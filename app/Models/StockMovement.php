<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * stock_movements hanya punya created_at. Stok bahan (ingredients.stock_qty) diubah
 * oleh trigger database trg_stock_movement_apply, JANGAN diubah manual dari PHP.
 */
#[Fillable(['ingredient_id', 'user_id', 'transaction_id', 'type', 'qty', 'notes'])]
class StockMovement extends Model
{
    public const UPDATED_AT = null;
}
