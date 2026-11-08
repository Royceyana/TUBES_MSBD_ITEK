<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tabel `transactions` (kafe_kaleng.sql).
 *   status       : pending -> completed (dibayar) | cancelled
 *   order_status : waiting -> preparing -> ready (dapur) -> served (pelayan). Saat 'ready',
 *                  trigger database memotong stok bahan sesuai resep.
 *   subtotal, discount_amount, total : dihitung trigger dari transaction_details + diskon
 *                  (discount_type none|percent|amount, discount_value). Diskon terkunci setelah lunas.
 */
#[Fillable(['user_id', 'transaction_date', 'payment_method', 'status', 'order_status', 'table_no', 'discount_type', 'discount_value'])]
class Transaction extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'completed';
    public const STATUS_VOID = 'cancelled';

    public const METHOD_LABEL = ['cash' => 'Tunai', 'qris' => 'QRIS'];
    public const STATUS_LABEL = ['pending' => 'Belum bayar', 'completed' => 'Lunas', 'cancelled' => 'Batal'];
    public const ORDER_LABEL = ['waiting' => 'Menunggu', 'preparing' => 'Dimasak', 'ready' => 'Siap diantar', 'served' => 'Diantar'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['transaction_date' => 'datetime', 'total' => 'integer', 'subtotal' => 'integer', 'discount_amount' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    /** Nomor tampilan, mis. INV-0042 (tidak disimpan, diturunkan dari id). */
    public function getNumberAttribute(): string
    {
        return 'INV-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHOD_LABEL[$this->payment_method] ?? '-';
    }
}
