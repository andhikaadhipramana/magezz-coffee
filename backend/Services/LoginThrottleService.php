<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LoginThrottleService
{
    private const MAX_ATTEMPTS = 3;

    private const BASE_LOCK_MINUTES = 5;

    private const MAX_LOCK_MINUTES = 60;

    private const FAILURE_WINDOW_MINUTES = 15;

    private const PENALTY_WINDOW_HOURS = 24;

    /**
     * Sisa waktu kunci dalam detik. Nol berarti tidak sedang terkunci.
     */
    public function remainingLockSeconds(string $email, ?string $ip): int
    {
        $lockedUntil = Cache::get($this->lockKey($email, $ip));

        if (! is_numeric($lockedUntil)) {
            return 0;
        }

        return max(0, (int) $lockedUntil - now()->timestamp);
    }

    /**
     * Sisa percobaan sebelum akun terkunci.
     */
    public function remainingAttempts(string $email, ?string $ip): int
    {
        $failures = (int) Cache::get($this->failureKey($email, $ip), 0);

        return max(0, self::MAX_ATTEMPTS - $failures);
    }

    /**
     * Catat satu kegagalan login.
     *
     * @return int|null Durasi kunci dalam menit jika akun baru saja terkunci.
     */
    public function recordFailure(string $email, ?string $ip): ?int
    {
        $failureKey = $this->failureKey($email, $ip);
        $failures = (int) Cache::get($failureKey, 0) + 1;

        if ($failures < self::MAX_ATTEMPTS) {
            Cache::put($failureKey, $failures, now()->addMinutes(self::FAILURE_WINDOW_MINUTES));

            return null;
        }

        Cache::forget($failureKey);

        $levelKey = $this->levelKey($email, $ip);
        $level = (int) Cache::get($levelKey, 0) + 1;
        Cache::put($levelKey, $level, now()->addHours(self::PENALTY_WINDOW_HOURS));

        $minutes = min(
            self::BASE_LOCK_MINUTES * $level,
            self::MAX_LOCK_MINUTES,
        );

        $lockedUntil = now()->addMinutes($minutes);
        Cache::put($this->lockKey($email, $ip), $lockedUntil->getTimestamp(), $lockedUntil);

        return $minutes;
    }

    /**
     * Hapus seluruh catatan throttle setelah login berhasil.
     */
    public function clear(string $email, ?string $ip): void
    {
        Cache::forget($this->failureKey($email, $ip));
        Cache::forget($this->lockKey($email, $ip));
        Cache::forget($this->levelKey($email, $ip));
    }

    private function failureKey(string $email, ?string $ip): string
    {
        return $this->key('failures', $email, $ip);
    }

    private function lockKey(string $email, ?string $ip): string
    {
        return $this->key('lock', $email, $ip);
    }

    private function levelKey(string $email, ?string $ip): string
    {
        return $this->key('level', $email, $ip);
    }

    private function key(string $prefix, string $email, ?string $ip): string
    {
        $identity = Str::lower(trim($email)).'|'.($ip ?: 'unknown');

        return 'login-throttle:'.$prefix.':'.hash('sha256', $identity);
    }
}
