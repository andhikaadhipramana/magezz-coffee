<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TransactionService
{
    public function generateCode(): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = 'MGC-'.$date.'-';

        do {
            $code = $prefix.Str::upper(Str::random(6));
        } while (Transaction::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Batalkan transaksi: tandai void dan catat siapa, kapan, serta alasan
     * pembatalannya.
     */
    public function void(Transaction $transaction, ?User $user, string $reason): void
    {
        $transaction->update([
            'status' => Transaction::STATUS_VOID,
            'voided_by' => $user?->id,
            'voided_at' => now(),
            'void_reason' => trim($reason),
        ]);
    }
}
