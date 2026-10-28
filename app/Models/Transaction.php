<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\TransactionDetail;

#[Fillable([
    'invoice_number', 'user_id', 'status',
    'subtotal', 'discount_type', 'discount_value', 'discount_amount', 'total',
    'payment_method', 'paid_amount', 'change_amount', 'paid_at', 'voided_at',
])]
class Transaction extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    /** Kasir yang membuat transaksi. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Item-item dalam transaksi. */
    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
