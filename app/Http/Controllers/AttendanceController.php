<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'date' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $user = $request->user();
        $isManager = $user->isManager();
        $selectedDate = $this->resolveDate($request->input('date'));
        $selectedUserId = $isManager
            ? (int) ($request->input('user_id') ?: $user->id)
            : $user->id;

        $selectedUser = User::query()->whereKey($selectedUserId)->first();

        if (! $selectedUser) {
            $selectedUser = $user;
            $selectedUserId = $user->id;
        }

        $attendances = Attendance::query()
            ->with('user')
            ->withCount('corrections')
            ->when(! $isManager, fn ($q) => $q->where('user_id', $user->id))
            ->when($isManager && $request->filled('user_id'), fn ($q) => $q->where('user_id', $request->input('user_id')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('date', $selectedDate))
            ->latest('date')
            ->paginate(20)
            ->withQueryString();

        $selectedAttendance = Attendance::query()
            ->where('user_id', $selectedUserId)
            ->whereDate('date', $selectedDate)
            ->first();

        $selectedShifts = Shift::query()
            ->with('closedBy')
            ->where('user_id', $selectedUserId)
            ->whereDate('started_at', $selectedDate)
            ->orderBy('started_at')
            ->get();

        $selectedTransactionQuery = Transaction::query()
            ->whereIn('shift_id', $selectedShifts->pluck('id'))
            ->where('status', Transaction::STATUS_COMPLETED);

        $selectedTransactionCount = (clone $selectedTransactionQuery)->count();
        $selectedTransactionTotal = (float) (clone $selectedTransactionQuery)->sum('total');
        $selectedCashDifference = (float) $selectedShifts->sum(
            fn (Shift $shift) => (float) ($shift->cash_difference ?? 0)
        );

        $corrections = $selectedAttendance
            ? $selectedAttendance->corrections()->with('editor')->latest()->limit(10)->get()
            : collect();

        $employees = $isManager
            ? User::query()->orderBy('name')->get()
            : collect();

        return view('attendances.index', compact(
            'attendances',
            'selectedAttendance',
            'selectedDate',
            'selectedUser',
            'selectedUserId',
            'selectedShifts',
            'selectedTransactionCount',
            'selectedTransactionTotal',
            'selectedCashDifference',
            'corrections',
            'employees',
            'isManager',
        ));
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['nullable', 'date', 'before_or_equal:today'],
            'clock_in' => ['required', 'date_format:H:i'],
        ], [
            'date.date' => 'Format tanggal tidak valid.',
            'date.before_or_equal' => 'Tanggal absensi tidak boleh melewati hari ini.',
            'clock_in.required' => 'Jam masuk wajib diisi.',
            'clock_in.date_format' => 'Format jam masuk tidak valid. Contoh: 08:30.',
        ]);

        $date = $this->resolveDate($data['date'] ?? null);

        if ($date !== today()->toDateString()) {
            return back()
                ->withInput()
                ->with('error', 'Absensi manual hanya untuk hari ini. Gunakan form koreksi absensi manager untuk tanggal lain.');
        }

        $attendance = $this->findForDate($request->user()->id, $date);

        if ($attendance && $attendance->clock_in) {
            return back()->with('error', 'Jam masuk tanggal '.$this->formatDate($date).' sudah dicatat pukul '.$attendance->clock_in->format('H:i').'.');
        }

        $this->saveTime($attendance, $request->user()->id, $date, 'clock_in', $data['clock_in']);

        return back()->with('success', 'Clock-in berhasil: '.$this->formatDate($date).' pukul '.$data['clock_in'].'.');
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['nullable', 'date', 'before_or_equal:today'],
            'clock_out' => ['required', 'date_format:H:i'],
        ], [
            'date.date' => 'Format tanggal tidak valid.',
            'date.before_or_equal' => 'Tanggal absensi tidak boleh melewati hari ini.',
            'clock_out.required' => 'Jam keluar wajib diisi.',
            'clock_out.date_format' => 'Format jam keluar tidak valid. Contoh: 17:00.',
        ]);

        $date = $this->resolveDate($data['date'] ?? null);

        if ($date !== today()->toDateString()) {
            return back()
                ->withInput()
                ->with('error', 'Absensi manual hanya untuk hari ini. Gunakan form koreksi absensi manager untuk tanggal lain.');
        }

        $attendance = $this->findForDate($request->user()->id, $date);

        if (! $attendance || ! $attendance->clock_in) {
            return back()->with('error', 'Belum ada jam masuk pada tanggal '.$this->formatDate($date).'. Lakukan clock-in terlebih dahulu.');
        }

        if ($attendance->clock_out) {
            return back()->with('error', 'Jam keluar tanggal '.$this->formatDate($date).' sudah dicatat pukul '.$attendance->clock_out->format('H:i').'.');
        }

        if ($data['clock_out'] < $attendance->clock_in->format('H:i')) {
            return back()->with('error', 'Jam keluar ('.$data['clock_out'].') tidak boleh lebih awal dari jam masuk ('.$attendance->clock_in->format('H:i').').');
        }

        $attendance->update(['clock_out' => $date.' '.$data['clock_out'].':00']);

        return back()->with('success', 'Clock-out berhasil: '.$this->formatDate($date).' pukul '.$data['clock_out'].'.');
    }

    public function correct(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'user_id.required' => 'Pilih karyawan yang akan dikoreksi.',
            'user_id.exists' => 'Karyawan tidak ditemukan.',
            'date.required' => 'Tanggal koreksi wajib diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'date.before_or_equal' => 'Tanggal koreksi tidak boleh melewati hari ini.',
            'clock_in.date_format' => 'Format jam masuk tidak valid. Contoh: 08:30.',
            'clock_out.date_format' => 'Format jam keluar tidak valid. Contoh: 17:00.',
            'reason.required' => 'Alasan koreksi wajib diisi.',
        ]);

        if (empty($data['clock_in']) && empty($data['clock_out'])) {
            return back()
                ->withInput()
                ->withErrors(['clock_in' => 'Isi minimal salah satu jam masuk atau jam keluar.']);
        }

        $date = Carbon::parse($data['date'])->toDateString();

        $result = DB::transaction(function () use ($data, $date, $request) {
            $attendance = Attendance::query()
                ->where('user_id', $data['user_id'])
                ->whereDate('date', $date)
                ->lockForUpdate()
                ->first();

            $oldClockIn = $attendance?->clock_in;
            $oldClockOut = $attendance?->clock_out;

            $newClockIn = ! empty($data['clock_in'])
                ? Carbon::parse($date.' '.$data['clock_in'])
                : $oldClockIn;
            $newClockOut = ! empty($data['clock_out'])
                ? Carbon::parse($date.' '.$data['clock_out'])
                : $oldClockOut;

            if (! $newClockIn && $newClockOut) {
                return ['error' => 'Jam keluar tidak dapat diisi tanpa jam masuk.'];
            }

            if ($newClockIn && $newClockOut && $newClockOut->lt($newClockIn)) {
                return ['error' => 'Jam keluar tidak boleh lebih awal dari jam masuk.'];
            }

            if ($this->sameTime($oldClockIn, $newClockIn) && $this->sameTime($oldClockOut, $newClockOut)) {
                return ['error' => 'Tidak ada perubahan pada absensi ini.'];
            }

            if (! $attendance) {
                $attendance = Attendance::create([
                    'user_id' => $data['user_id'],
                    'date' => $date,
                    'clock_in' => $newClockIn,
                    'clock_out' => $newClockOut,
                ]);
            } else {
                $attendance->update([
                    'clock_in' => $newClockIn,
                    'clock_out' => $newClockOut,
                ]);
            }

            $attendance->corrections()->create([
                'edited_by' => $request->user()->id,
                'old_clock_in' => $oldClockIn,
                'old_clock_out' => $oldClockOut,
                'new_clock_in' => $newClockIn,
                'new_clock_out' => $newClockOut,
                'reason' => trim($data['reason']),
            ]);

            return ['attendance' => $attendance];
        });

        if (isset($result['error'])) {
            return back()->withInput()->with('error', $result['error']);
        }

        $employee = User::query()->find($data['user_id']);

        return back()->with('success', 'Absensi '.$employee?->name.' tanggal '.$this->formatDate($date).' berhasil dikoreksi.');
    }

    private function findForDate(int $userId, string $date): ?Attendance
    {
        return Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('date', $date)
            ->first();
    }

    private function saveTime(?Attendance $attendance, int $userId, string $date, string $field, string $time): void
    {
        $value = $date.' '.$time.':00';

        if ($attendance) {
            $attendance->update([$field => $value]);

            return;
        }

        Attendance::create([
            'user_id' => $userId,
            'date' => $date,
            $field => $value,
        ]);
    }

    private function resolveDate(?string $value): string
    {
        if (! $value || strtotime($value) === false) {
            return today()->toDateString();
        }

        $date = Carbon::parse($value)->toDateString();

        return $date > today()->toDateString()
            ? today()->toDateString()
            : $date;
    }

    private function sameTime(?Carbon $old, ?Carbon $new): bool
    {
        if ($old === null || $new === null) {
            return $old === $new;
        }

        return $old->equalTo($new);
    }

    private function formatDate(string $date): string
    {
        return Carbon::parse($date)->format('d/m/Y');
    }
}
