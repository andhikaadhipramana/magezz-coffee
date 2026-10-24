<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$start, $end] = $this->resolvePeriod($request);

        $baseQuery = Transaction::query()
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id));

        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as trx_count')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as subtotal')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discount')
            ->selectRaw('COALESCE(SUM(tax_amount), 0) as tax')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->first();

        $itemsSold = TransactionItem::query()
            ->whereHas('transaction', function ($q) use ($start, $end, $request) {
                $q->where('status', Transaction::STATUS_COMPLETED)
                    ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                    ->when($request->filled('payment_method'), fn ($sub) => $sub->where('payment_method', $request->payment_method))
                    ->when($request->filled('user_id'), fn ($sub) => $sub->where('user_id', $request->user_id));
            })
            ->sum('quantity');

        $bestSellers = TransactionItem::query()
            ->select('menu_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
            ->whereHas('transaction', function ($q) use ($start, $end, $request) {
                $q->where('status', Transaction::STATUS_COMPLETED)
                    ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                    ->when($request->filled('payment_method'), fn ($sub) => $sub->where('payment_method', $request->payment_method))
                    ->when($request->filled('user_id'), fn ($sub) => $sub->where('user_id', $request->user_id));
            })
            ->groupBy('menu_name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        $byPayment = (clone $baseQuery)
            ->select('payment_method', DB::raw('COUNT(*) as trx_count'), DB::raw('SUM(total) as revenue'))
            ->groupBy('payment_method')
            ->get();

        $daily = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as tanggal, COUNT(*) as trx_count, SUM(total) as revenue')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        return view('reports.index', [
            'start' => $start,
            'end' => $end,
            'summary' => $summary,
            'itemsSold' => (int) $itemsSold,
            'bestSellers' => $bestSellers,
            'byPayment' => $byPayment,
            'daily' => $daily,
            'users' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$start, $end] = $this->resolvePeriod($request);

        $transactions = Transaction::query()
            ->with('user')
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->orderBy('created_at')
            ->get();

        $filename = 'laporan-penjualan-'.$start->format('Ymd').'-'.$end->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Kode', 'Tanggal', 'Kasir', 'Metode', 'Subtotal', 'Diskon', 'Pajak', 'Total']);

            foreach ($transactions as $trx) {
                fputcsv($handle, [
                    $trx->code,
                    $trx->created_at->format('Y-m-d H:i'),
                    $trx->user?->name,
                    $trx->paymentLabel(),
                    number_format((float) $trx->subtotal, 2, '.', ''),
                    number_format((float) $trx->discount_amount, 2, '.', ''),
                    number_format((float) $trx->tax_amount, 2, '.', ''),
                    number_format((float) $trx->total, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function resolvePeriod(Request $request): array
    {
        $start = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : Carbon::today();

        $end = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::today();

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }
}
