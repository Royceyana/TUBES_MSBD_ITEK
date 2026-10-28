<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['transaction_id', 'menu_id', 'menu_name', 'price', 'qty', 'subtotal'])]
class TransactionDetail extends Model
{
    /** Transaksi induk. */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    // Relasi menu() ditambahkan setelah model Menu dari anggota lain tersedia.
}
