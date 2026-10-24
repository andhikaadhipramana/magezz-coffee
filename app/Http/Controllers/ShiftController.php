<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'date' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'in:open,closed'],
        ]);

        $user = $request->user();
        $isManager = $user->isManager();

        $baseQuery = Shift::query()
            ->when(! $isManager, fn ($q) => $q->where('user_id', $user->id))
            ->when($isManager && $request->filled('user_id'), fn ($q) => $q->where('user_id', $request->input('user_id')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('started_at', $request->input('date')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')));

        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(cash_difference), 0) as difference')
            ->first();

        $shifts = (clone $baseQuery)
            ->with(['user', 'closedBy'])
            ->latest('started_at')
            ->paginate(15)
            ->withQueryString();

        $users = $isManager
            ? User::query()->orderBy('name')->get()
            : collect();

        $activeShift = $user->activeShift();

        return view('shifts.index', compact('shifts', 'activeShift', 'summary', 'users', 'isManager'));
    }

    public function open(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $result = DB::transaction(function () use ($request, $data) {
            $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

            if ($user->activeShift()) {
                return ['error' => 'Anda masih memiliki shift yang belum ditutup.'];
            }

            $now = now();

            Shift::create([
                'user_id' => $user->id,
                'started_at' => $now,
                'opening_cash' => $data['opening_cash'],
                'status' => Shift::STATUS_OPEN,
                'notes' => $data['notes'] ?? null,
            ]);

            $attendance = Attendance::query()->firstOrCreate([
                'user_id' => $user->id,
                'date' => $now->toDateString(),
            ]);

            if (! $attendance->clock_in || $now->lt($attendance->clock_in)) {
                $attendance->update(['clock_in' => $now]);
            }

            return ['ok' => true];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', 'Shift berhasil dibuka. Selamat bekerja!');
    }

    public function close(Request $request, Shift $shift): RedirectResponse
    {
        if ($shift->user_id !== $request->user()->id && ! $request->user()->isManager()) {
            abort(403);
        }

        $data = $request->validate([
            'closing_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $result = DB::transaction(function () use ($request, $shift, $data) {
            $locked = Shift::query()->whereKey($shift->id)->lockForUpdate()->first();

            if (! $locked) {
                return ['error' => 'Shift tidak ditemukan.'];
            }

            if (! $locked->isOpen()) {
                return ['error' => 'Shift ini sudah ditutup.'];
            }

            $cashSales = $locked->cashSalesTotal();
            $expected = (float) $locked->opening_cash + $cashSales;
            $difference = (float) $data['closing_cash'] - $expected;
            $notes = trim((string) ($data['notes'] ?? ''));

            if (abs($difference) >= 0.01 && $notes === '') {
                return ['error' => 'Selisih kas Rp '.number_format(abs($difference), 0, ',', '.').' wajib disertai catatan alasan.'];
            }

            $locked->update([
                'ended_at' => now(),
                'closing_cash' => $data['closing_cash'],
                'expected_cash' => $expected,
                'cash_difference' => $difference,
                'status' => Shift::STATUS_CLOSED,
                'closed_by' => $request->user()->id,
                'notes' => implode(' | ', array_filter([
                    $locked->notes,
                    $notes,
                ], fn ($value) => $value !== null && trim((string) $value) !== '')) ?: null,
            ]);

            $this->syncAttendanceOnClose($locked);

            return ['difference' => $difference];
        });

        if (isset($result['error'])) {
            return back()->withInput()->with('error', $result['error']);
        }

        $difference = (float) $result['difference'];

        $message = abs($difference) < 0.01
            ? 'Shift ditutup. Kas sesuai (tidak ada selisih).'
            : 'Shift ditutup dengan selisih Rp '.number_format(abs($difference), 0, ',', '.').' ('.($difference > 0 ? 'surplus' : 'defisit').').';

        return back()->with('success', $message);
    }

    public function show(Request $request, Shift $shift): View
    {
        if (! $request->user()->isManager() && $shift->user_id !== $request->user()->id) {
            abort(403);
        }

        $shift->load(['user', 'closedBy', 'transactions.items']);

        return view('shifts.show', compact('shift'));
    }

    private function syncAttendanceOnClose(Shift $shift): void
    {
        if (! $shift->started_at) {
            return;
        }

        $now = now();
        $attendance = Attendance::query()->firstOrCreate([
            'user_id' => $shift->user_id,
            'date' => $shift->started_at->toDateString(),
        ]);

        if (! $attendance->clock_in || $now->lt($attendance->clock_in)) {
            $attendance->clock_in = $now;
        }

        if (! $attendance->clock_out || $now->gt($attendance->clock_out)) {
            $attendance->clock_out = $now;
        }

        $attendance->save();
    }
}
