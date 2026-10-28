<?php

namespace App\Http\Controllers;

use App\Services\LoginThrottleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginThrottleService $loginThrottle,
    ) {}

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials['email'] = Str::lower(trim($credentials['email']));
        $ip = $request->ip();

        $lockSeconds = $this->loginThrottle->remainingLockSeconds($credentials['email'], $ip);

        if ($lockSeconds > 0) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $this->lockMessage($lockSeconds)]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $lockedMinutes = $this->loginThrottle->recordFailure($credentials['email'], $ip);

            if ($lockedMinutes !== null) {
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => "Terlalu banyak percobaan login. Coba lagi dalam {$lockedMinutes} menit."]);
            }

            $remainingAttempts = $this->loginThrottle->remainingAttempts($credentials['email'], $ip);
            $message = 'Email atau kata sandi salah.';

            if ($remainingAttempts > 0) {
                $message .= " Sisa {$remainingAttempts} percobaan sebelum akun dikunci.";
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $message]);
        }

        $this->loginThrottle->clear($credentials['email'], $ip);

        if (! Auth::user()->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'Akun Anda tidak aktif. Hubungi Manager.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Selamat datang, '.Auth::user()->name.'!');
    }

    private function lockMessage(int $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return "Terlalu banyak percobaan login. Coba lagi dalam {$minutes} menit.";
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar dari sistem.');
    }
}
