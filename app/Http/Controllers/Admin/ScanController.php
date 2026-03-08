<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LaundryOrder;
use App\Services\WhatsappService;
use App\Support\LaundryStatus;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function __construct(private readonly WhatsappService $whatsappService)
    {
    }

    public function index(): View
    {
        return view('admin.scan.index', [
            'statuses' => LaundryStatus::values(),
            'statusLabels' => LaundryStatus::labels(),
            'defaultStatus' => LaundryStatus::WASHING,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:40'],
            'status' => ['required', Rule::in(LaundryStatus::values())],
            'note' => ['nullable', 'string'],
        ]);

        $orderNumber = strtoupper(trim($data['order_number']));

        $laundryOrder = LaundryOrder::query()
            ->with('customer')
            ->where('order_number', $orderNumber)
            ->first();

        if (! $laundryOrder) {
            return back()
                ->withInput()
                ->with('error', 'Order tidak ditemukan: '.$orderNumber);
        }

        if (! LaundryStatus::canTransition($laundryOrder->status, $data['status'])) {
            return back()
                ->withInput()
                ->with('error', 'Perpindahan status tidak valid untuk order '.$orderNumber.'.');
        }

        $laundryOrder->update([
            'status' => $data['status'],
            'status_note' => $data['note'] ?? null,
            'pickup_at' => $data['status'] === LaundryStatus::PICKED_UP ? Carbon::now() : $laundryOrder->pickup_at,
            'updated_by' => $request->user()?->id,
        ]);

        $laundryOrder->statusHistories()->create([
            'status' => $data['status'],
            'note' => $data['note'] ?? 'Update dari station scan.',
            'changed_by' => $request->user()?->id,
            'changed_at' => Carbon::now(),
        ]);

        $statusLabel = LaundryStatus::labels()[$data['status']] ?? $data['status'];
        $waResult = $this->whatsappService->sendStatusUpdate(
            $laundryOrder->fresh(['customer']),
            $statusLabel,
            $data['note'] ?? null,
        );

        $response = back()->with('success', 'Scan berhasil. Order '.$orderNumber.' diupdate ke status '.$statusLabel.'.');

        if (($waResult['sent'] ?? false) === false) {
            if (($waResult['skipped'] ?? false) === true) {
                $response->with('warning', 'Status tersimpan. Notifikasi WhatsApp dilewati karena konfigurasi belum lengkap.');
            } else {
                $response->with('error', 'Status tersimpan, tetapi notifikasi WhatsApp gagal dikirim.');
            }
        }

        return $response;
    }
}
