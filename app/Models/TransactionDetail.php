<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Baris item pesanan. subtotal = qty x harga menu saat dipesan (diisi aplikasi). */
#[Table(timestamps: false)]
#[Fillable(['transaction_id', 'menu_id', 'qty', 'subtotal'])]
class TransactionDetail extends Model
{
    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2'];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
