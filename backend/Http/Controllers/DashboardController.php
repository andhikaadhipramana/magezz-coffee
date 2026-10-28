<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->isManager();
        $today = Carbon::today();

        // Kasir hanya melihat angka miliknya sendiri; Manager melihat seluruh toko.
        $ownScope = function ($query) use ($isManager, $user) {
            if (! $isManager) {
                $query->where('user_id', $user->id);
            }

            return $query;
        };

        $todayStats = $ownScope(
            Transaction::query()
                ->where('status', Transaction::STATUS_COMPLETED)
                ->whereDate('created_at', $today)
        )
            ->selectRaw('COUNT(*) as trx_count, COALESCE(SUM(total), 0) as revenue')
            ->first();

        $monthRevenue = $ownScope(
            Transaction::query()
                ->where('status', Transaction::STATUS_COMPLETED)
                ->whereMonth('created_at', $today->month)
                ->whereYear('created_at', $today->year)
        )->sum('total');

        $itemsToday = TransactionItem::query()
            ->whereHas('transaction', function ($q) use ($today, $ownScope) {
                $q->where('status', Transaction::STATUS_COMPLETED)
                    ->whereDate('created_at', $today);

                $ownScope($q);
            })
            ->sum('quantity');

        $bestSellers = TransactionItem::query()
            ->select('menu_name', DB::raw('SUM(quantity) as total_qty'))
            ->whereHas('transaction', function ($q) use ($ownScope) {
                $q->where('status', Transaction::STATUS_COMPLETED);

                $ownScope($q);
            })
            ->groupBy('menu_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $recentTransactions = $ownScope(
            Transaction::query()
                ->with('user')
                ->where('status', Transaction::STATUS_COMPLETED)
        )
            ->latest()
            ->limit(8)
            ->get();

        $weeklySales = collect(range(6, 0))->map(function ($daysAgo) use ($ownScope) {
            $date = Carbon::today()->subDays($daysAgo);
            $total = $ownScope(
                Transaction::query()
                    ->where('status', Transaction::STATUS_COMPLETED)
                    ->whereDate('created_at', $date)
            )->sum('total');

            return [
                'label' => $date->translatedFormat('d M'),
                'total' => (float) $total,
            ];
        });

        return view('dashboard', [
            'isManager' => $isManager,
            'todayRevenue' => (float) $todayStats->revenue,
            'todayTrx' => (int) $todayStats->trx_count,
            'monthRevenue' => (float) $monthRevenue,
            'itemsToday' => (int) $itemsToday,
            'menuCount' => Menu::query()->where('is_active', true)->count(),
            'bestSellers' => $bestSellers,
            'recentTransactions' => $recentTransactions,
            'weeklySales' => $weeklySales,
            'maxWeekly' => max(1, $weeklySales->max('total')),
        ]);
    }
}
