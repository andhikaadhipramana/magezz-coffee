<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_QRIS = 'qris';

    public const PAYMENT_CARD = 'card';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_VOID = 'void';

    protected $fillable = [
        'code',
        'user_id',
        'shift_id',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_amount',
        'total',
        'payment_method',
        'paid_amount',
        'change_amount',
        'status',
        'notes',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'voided_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function paymentLabel(): string
    {
        return match ($this->payment_method) {
            self::PAYMENT_CASH => 'Tunai',
            self::PAYMENT_QRIS => 'QRIS',
            self::PAYMENT_CARD => 'Kartu',
            default => strtoupper($this->payment_method),
        };
    }
}
