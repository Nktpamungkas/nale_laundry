<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\LaundryOrder;
use App\Models\Payment;
use App\Support\LaundryStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $statusCounts = LaundryOrder::query()
            ->select('status', DB::raw('COUNT(*) AS total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $monthStart = Carbon::now()->startOfMonth();

        $monthSales = (float) LaundryOrder::query()
            ->where('created_at', '>=', $monthStart)
            ->sum('grand_total');

        $monthHpp = (float) LaundryOrder::query()
            ->where('created_at', '>=', $monthStart)
            ->sum('hpp_total');

        $monthRevenue = (float) Payment::query()
            ->where('payment_date', '>=', $monthStart)
            ->sum('amount');

        $todayRevenue = (float) Payment::query()
            ->whereDate('payment_date', Carbon::today())
            ->sum('amount');

        $lowStockItems = InventoryItem::query()
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->orderBy('current_stock')
            ->limit(10)
            ->get();

        $recentOrders = LaundryOrder::query()
            ->with('customer')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.dashboard', [
            'statusCounts' => $statusCounts,
            'statusLabels' => LaundryStatus::labels(),
            'totalOrders' => LaundryOrder::query()->count(),
            'inProgressOrders' => LaundryOrder::query()->whereNotIn('status', [LaundryStatus::PICKED_UP, LaundryStatus::CANCELED])->count(),
            'monthSales' => $monthSales,
            'monthHpp' => $monthHpp,
            'monthGrossProfit' => $monthSales - $monthHpp,
            'monthRevenue' => $monthRevenue,
            'todayRevenue' => $todayRevenue,
            'lowStockItems' => $lowStockItems,
            'recentOrders' => $recentOrders,
        ]);
    }
}
