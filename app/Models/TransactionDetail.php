<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** transaction_details(transaction_id, menu_id, qty, subtotal). Nama & harga diambil lewat relasi menu. */
#[Fillable(['transaction_id', 'menu_id', 'qty', 'subtotal'])]
class TransactionDetail extends Model
{
    public $timestamps = false;

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
