<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_READY = 'ready';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code',
        'customer_name',
        'table_number',
        'notes',
        'subtotal',
        'tax_amount',
        'total',
        'status',
        'paid_at',
        'paid_by',
        'transaction_id',
        'cancel_reason',
        'cancelled_by',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu Pembayaran',
            self::STATUS_IN_PROGRESS => 'Diproses',
            self::STATUS_READY => 'Siap Diambil',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    public function badgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'badge-soft-warning',
            self::STATUS_IN_PROGRESS => 'badge-soft-info',
            self::STATUS_READY => 'badge-soft-success',
            self::STATUS_COMPLETED => 'badge-soft-secondary',
            self::STATUS_CANCELLED => 'badge-soft-danger',
            default => 'badge-soft-secondary',
        };
    }

    public function hasTax(): bool
    {
        return (float) $this->tax_amount > 0;
    }

    /**
     * Status tujuan yang masih boleh dipilih dari status saat ini.
     *
     * Catatan: pending hanya berpindah ke in_progress melalui pembayaran di POS,
     * bukan lewat tombol status manual.
     *
     * @return array<int, string>
     */
    public function nextStatuses(): array
    {
        return match ($this->status) {
            self::STATUS_PENDING => [self::STATUS_CANCELLED],
            self::STATUS_IN_PROGRESS => [self::STATUS_READY, self::STATUS_CANCELLED],
            self::STATUS_READY => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            default => [],
        };
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    /**
     * Posisi pesanan pada garis waktu customer: 1 = Menunggu Pembayaran,
     * 2 = Diproses, 3 = Siap Diambil, 4 = Selesai.
     */
    public function stepNumber(): int
    {
        return match ($this->status) {
            self::STATUS_PENDING => 1,
            self::STATUS_IN_PROGRESS => 2,
            self::STATUS_READY => 3,
            default => 4,
        };
    }

    /**
     * Tandai pesanan lunas: pembayaran dilakukan kasir lewat POS,
     * pesanan otomatis masuk antrean pengerjaan (Diproses).
     */
    public function markPaid(Transaction $transaction, ?User $user): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'paid_at' => now(),
            'paid_by' => $user?->id,
            'transaction_id' => $transaction->id,
        ]);
    }

    public function createdAtLabel(): string
    {
        return $this->created_at instanceof Carbon
            ? $this->created_at->format('d/m/Y H:i')
            : (string) $this->created_at;
    }
}
