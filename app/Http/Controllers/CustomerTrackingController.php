<?php

namespace App\Http\Controllers;

use App\Models\LaundryOrder;
use App\Support\LaundryStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerTrackingController extends Controller
{
    public function index(): View
    {
        return view('tracking.index', [
            'order' => null,
            'statusLabels' => LaundryStatus::labels(),
        ]);
    }

    public function track(Request $request): View
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $order = LaundryOrder::query()
            ->with(['customer', 'items.servicePackage', 'statusHistories' => fn ($query) => $query->latest('changed_at')])
            ->where('order_number', $data['order_number'])
            ->whereHas('customer', function ($query) use ($data) {
                $query->where('phone', $data['phone']);
            })
            ->first();

        if (! $order) {
            return view('tracking.index', [
                'order' => null,
                'statusLabels' => LaundryStatus::labels(),
            ])->withErrors([
                'order_number' => 'Order tidak ditemukan. Pastikan nomor order dan nomor HP benar.',
            ]);
        }

        return view('tracking.index', [
            'order' => $order,
            'statusLabels' => LaundryStatus::labels(),
        ]);
    }
}
