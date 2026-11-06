<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu pesanan. Alur: waitress membuat (pending/waiting) -> dapur (preparing, ready)
 * -> waitress mengantar (served) -> kasir menerima bayaran (completed).
 *
 * subtotal, discount_amount, dan total dihitung trigger database dari transaction_details
 * serta discount_type/discount_value, jadi tidak ada di $fillable.
 */
#[Table(timestamps: false)]
#[Fillable(['user_id', 'transaction_date', 'payment_method', 'status', 'order_status', 'table_no', 'discount_type', 'discount_value'])]
class Transaction extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const ORDER_WAITING = 'waiting';
    public const ORDER_PREPARING = 'preparing';
    public const ORDER_READY = 'ready';
    public const ORDER_SERVED = 'served';

    public const PAYMENT_CASH = 'cash';
    public const PAYMENT_QRIS = 'qris';
    public const PAYMENT_DEBIT = 'debit';

    public const DISCOUNT_NONE = 'none';
    public const DISCOUNT_PERCENT = 'percent';
    public const DISCOUNT_AMOUNT = 'amount';

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'order_status' => self::ORDER_WAITING,
        'discount_type' => self::DISCOUNT_NONE,
        'discount_value' => 0,
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'total' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    /** Waitress / kasir yang membuat pesanan. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** Antrian dapur: belum dibatalkan dan belum siap. */
    public function scopeForKitchen(Builder $query): Builder
    {
        return $query->where('status', '<>', self::STATUS_CANCELLED)
            ->whereIn('order_status', [self::ORDER_WAITING, self::ORDER_PREPARING]);
    }

    /** Sudah siap di dapur dan menunggu diantar waitress. */
    public function scopeReadyToServe(Builder $query): Builder
    {
        return $query->where('status', '<>', self::STATUS_CANCELLED)
            ->where('order_status', self::ORDER_READY);
    }

    /** Menunggu pembayaran di kasir. */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
