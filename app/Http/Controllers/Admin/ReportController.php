<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LaundryOrder;
use App\Models\Payment;
use App\Support\LaundryStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function monthly(Request $request): View
    {
        $monthInput = $request->string('month')->toString();
        $selectedMonth = $this->resolveMonth($monthInput);

        $start = $selectedMonth->copy()->startOfMonth();
        $end = $selectedMonth->copy()->endOfMonth();

        $orders = LaundryOrder::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', '!=', LaundryStatus::CANCELED);

        $totalOrders = (clone $orders)->count();
        $totalSales = (float) (clone $orders)->sum('grand_total');
        $totalHpp = (float) (clone $orders)->sum('hpp_total');
        $grossProfit = $totalSales - $totalHpp;
        $grossMargin = $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0;

        $paymentsReceived = (float) Payment::query()
            ->whereBetween('payment_date', [$start, $end])
            ->sum('amount');

        $inventoryPurchases = (float) DB::table('stock_movements')
            ->whereBetween('movement_date', [$start, $end])
            ->where('movement_type', 'purchase')
            ->sum('total_cost');

        $inventoryPurchasesFromStoreCash = (float) DB::table('stock_movements')
            ->whereBetween('movement_date', [$start, $end])
            ->where('movement_type', 'purchase')
            ->where(function ($query) {
                $query->where('funding_source', 'kas_toko')
                    ->orWhereNull('funding_source');
            })
            ->sum('total_cost');

        $purchaseByFundingSource = DB::table('stock_movements')
            ->select(
                DB::raw("COALESCE(funding_source, 'kas_toko') as funding_source"),
                DB::raw('SUM(total_cost) as total_cost')
            )
            ->whereBetween('movement_date', [$start, $end])
            ->where('movement_type', 'purchase')
            ->groupBy(DB::raw("COALESCE(funding_source, 'kas_toko')"))
            ->get()
            ->map(function ($row) {
                $label = match($row->funding_source) {
                    'dana_owner' => 'Dana Owner',
                    'hutang_supplier' => 'Hutang Supplier',
                    default => 'Kas Toko',
                };

                return [
                    'funding_source' => $row->funding_source,
                    'funding_source_label' => $label,
                    'total_cost' => (float) $row->total_cost,
                ];
            })
            ->sortByDesc('total_cost')
            ->values();

        $distributableCash = $paymentsReceived - $inventoryPurchasesFromStoreCash;
        $shareBaseAmount = max($distributableCash, 0);

        $defaultShareNames = ['Pemilik Toko', 'Mamah', 'Karyawan 2'];
        $defaultSharePercents = [60, 25, 15];

        $shareNames = $request->input('share_names', $defaultShareNames);
        $sharePercents = $request->input('share_percents', $defaultSharePercents);

        if (! is_array($shareNames)) {
            $shareNames = $defaultShareNames;
        }
        if (! is_array($sharePercents)) {
            $sharePercents = $defaultSharePercents;
        }

        $shareInputs = collect(range(0, max(count($shareNames), count($sharePercents)) - 1))
            ->map(function (int $index) use ($shareNames, $sharePercents) {
                $name = trim((string) ($shareNames[$index] ?? ''));
                $percent = (float) ($sharePercents[$index] ?? 0);

                return [
                    'name' => $name !== '' ? $name : 'Penerima '.($index + 1),
                    'percent' => max($percent, 0),
                ];
            })
            ->filter(fn (array $row) => $row['name'] !== '' || $row['percent'] > 0)
            ->values();

        if ($shareInputs->isEmpty()) {
            $shareInputs = collect([
                ['name' => 'Pemilik Toko', 'percent' => 100],
            ]);
        }

        $shareRows = $shareInputs->map(function (array $row) use ($shareBaseAmount) {
            return [
                'name' => $row['name'],
                'percent' => $row['percent'],
                'amount' => round($shareBaseAmount * ($row['percent'] / 100), 2),
            ];
        })->values();

        $totalSharePercent = (float) $shareRows->sum('percent');
        $totalShareAmount = (float) $shareRows->sum('amount');

        $employeeRevenue = Payment::query()
            ->select('received_by', DB::raw('COUNT(*) as total_transactions'), DB::raw('SUM(amount) as total_amount'))
            ->with('receiver:id,name')
            ->whereBetween('payment_date', [$start, $end])
            ->groupBy('received_by')
            ->get()
            ->map(function (Payment $payment) {
                $totalAmount = (float) $payment->total_amount;
                $totalTransactions = (int) $payment->total_transactions;

                return [
                    'employee_name' => $payment->receiver?->name ?? 'Tanpa Petugas',
                    'total_transactions' => $totalTransactions,
                    'total_amount' => $totalAmount,
                    'avg_per_transaction' => $totalTransactions > 0 ? $totalAmount / $totalTransactions : 0,
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        $statusCounts = LaundryOrder::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $packageBreakdown = DB::table('laundry_order_items as loi')
            ->join('laundry_orders as lo', 'lo.id', '=', 'loi.laundry_order_id')
            ->join('service_packages as sp', 'sp.id', '=', 'loi.service_package_id')
            ->whereBetween('lo.created_at', [$start, $end])
            ->where('lo.status', '!=', LaundryStatus::CANCELED)
            ->groupBy('sp.name')
            ->orderByDesc(DB::raw('SUM(loi.line_total)'))
            ->selectRaw('sp.name as package_name, SUM(loi.quantity) as total_qty, SUM(loi.line_total) as total_sales, SUM(loi.hpp_total) as total_hpp')
            ->get();

        $trend = collect(range(5, 0))
            ->map(fn (int $offset) => Carbon::now()->subMonths($offset))
            ->push(Carbon::now())
            ->map(function (Carbon $month) {
                $monthStart = $month->copy()->startOfMonth();
                $monthEnd = $month->copy()->endOfMonth();

                $sales = (float) LaundryOrder::query()
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->where('status', '!=', LaundryStatus::CANCELED)
                    ->sum('grand_total');

                $hpp = (float) LaundryOrder::query()
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->where('status', '!=', LaundryStatus::CANCELED)
                    ->sum('hpp_total');

                return [
                    'month' => $month->format('M Y'),
                    'sales' => $sales,
                    'hpp' => $hpp,
                    'profit' => $sales - $hpp,
                ];
            });

        return view('admin.reports.monthly', [
            'selectedMonth' => $selectedMonth,
            'totalOrders' => $totalOrders,
            'totalSales' => $totalSales,
            'totalHpp' => $totalHpp,
            'grossProfit' => $grossProfit,
            'grossMargin' => $grossMargin,
            'paymentsReceived' => $paymentsReceived,
            'inventoryPurchases' => $inventoryPurchases,
            'inventoryPurchasesFromStoreCash' => $inventoryPurchasesFromStoreCash,
            'purchaseByFundingSource' => $purchaseByFundingSource,
            'distributableCash' => $distributableCash,
            'shareBaseAmount' => $shareBaseAmount,
            'employeeRevenue' => $employeeRevenue,
            'shareRows' => $shareRows,
            'totalSharePercent' => $totalSharePercent,
            'totalShareAmount' => $totalShareAmount,
            'outstanding' => max($totalSales - $paymentsReceived, 0),
            'statusCounts' => $statusCounts,
            'statusLabels' => LaundryStatus::labels(),
            'packageBreakdown' => $packageBreakdown,
            'trend' => $trend,
        ]);
    }

    private function resolveMonth(string $input): Carbon
    {
        if ($input === '') {
            return Carbon::now();
        }

        try {
            return Carbon::createFromFormat('Y-m', $input);
        } catch (\Throwable) {
            return Carbon::now();
        }
    }
}
