<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\LaundryOrder;
use App\Models\Payment;
use App\Models\ServicePackage;
use App\Models\StockMovement;
use App\Services\BarcodeService;
use App\Services\HppCalculator;
use App\Services\InventoryService;
use App\Services\WhatsappService;
use App\Support\LaundryStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LaundryOrderController extends Controller
{
    public function __construct(
        private readonly BarcodeService $barcodeService,
        private readonly HppCalculator $hppCalculator,
        private readonly InventoryService $inventoryService,
        private readonly WhatsappService $whatsappService,
    ) {
    }

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $paymentStatus = $request->string('payment_status')->toString();

        $laundryOrders = LaundryOrder::query()
            ->with('customer')
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->when($paymentStatus, fn ($query, $paymentStatus) => $query->where('payment_status', $paymentStatus))
            ->latest('received_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.laundry-orders.index', [
            'laundryOrders' => $laundryOrders,
            'status' => $status,
            'paymentStatus' => $paymentStatus,
            'statusLabels' => LaundryStatus::labels(),
        ]);
    }

    public function create(): View
    {
        $customers = Customer::query()->orderBy('name')->get();
        $servicePackages = ServicePackage::query()
            ->where('is_active', true)
            ->with(['materials.inventoryItem'])
            ->orderBy('name')
            ->get();

        return view('admin.laundry-orders.create', compact('customers', 'servicePackages'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_mode' => ['required', Rule::in(['existing', 'new'])],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id', 'required_if:customer_mode,existing'],
            'new_customer_name' => ['nullable', 'string', 'max:120', 'required_if:customer_mode,new'],
            'new_customer_phone' => ['nullable', 'string', 'max:30', 'required_if:customer_mode,new'],
            'new_customer_email' => ['nullable', 'email', 'max:255'],
            'received_at' => ['required', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:received_at'],
            'status_note' => ['nullable', 'string'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_package_id' => ['required', 'integer', 'exists:service_packages,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.description' => ['nullable', 'string'],
        ]);

        if (($data['customer_mode'] ?? 'existing') === 'new') {
            $newPhone = trim((string) ($data['new_customer_phone'] ?? ''));
            $data['new_customer_phone'] = $newPhone;

            if (Customer::query()->where('phone', $newPhone)->exists()) {
                return back()
                    ->withInput()
                    ->withErrors(['new_customer_phone' => 'Nomor WhatsApp sudah terdaftar.'])
                    ->with('warning', 'Nomor WhatsApp sudah ada. Pilih pelanggan yang sudah terdaftar.');
            }
        }

        $servicePackages = ServicePackage::query()
            ->with('materials.inventoryItem')
            ->whereIn('id', collect($data['items'])->pluck('service_package_id')->all())
            ->get()
            ->keyBy('id');

        $availability = $this->inventoryService->checkStockForDraftItems($data['items'], $servicePackages);
        if (! $availability['is_available']) {
            return back()
                ->withInput()
                ->with('error', 'Stok bahan tidak cukup untuk order ini.')
                ->withErrors(['stock' => $this->buildStockIssueMessages($availability['issues'])]);
        }

        $customerWasCreated = false;

        $laundryOrder = DB::transaction(function () use ($data, $servicePackages, $request, &$customerWasCreated) {
            if ($data['customer_mode'] === 'new') {
                $customer = Customer::query()->create([
                    'code' => $this->nextCustomerCode(),
                    'name' => trim((string) ($data['new_customer_name'] ?? '')),
                    'phone' => trim((string) ($data['new_customer_phone'] ?? '')),
                    'email' => filled($data['new_customer_email'] ?? null) ? trim((string) $data['new_customer_email']) : null,
                ]);
                $customerWasCreated = true;

                $customerId = $customer->id;
            } else {
                $customerId = (int) $data['customer_id'];
            }

            $order = LaundryOrder::query()->create([
                'order_number' => $this->nextOrderNumber(),
                'customer_id' => $customerId,
                'received_at' => $data['received_at'],
                'due_at' => $data['due_at'] ?? null,
                'status' => LaundryStatus::RECEIVED,
                'status_note' => $data['status_note'] ?? null,
                'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                'tax_amount' => 0,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            $subtotal = 0.0;
            $hppTotal = 0.0;

            foreach ($data['items'] as $line) {
                /** @var ServicePackage $servicePackage */
                $servicePackage = $servicePackages->get($line['service_package_id']);
                $quantity = (float) $line['quantity'];
                $unitPrice = isset($line['unit_price']) ? (float) $line['unit_price'] : (float) $servicePackage->sale_price;
                $lineTotal = round($quantity * $unitPrice, 2);
                $hpp = $this->hppCalculator->calculateLine($servicePackage, $quantity);

                $subtotal += $lineTotal;
                $hppTotal += $hpp['hpp_total'];

                $order->items()->create([
                    'service_package_id' => $servicePackage->id,
                    'description' => $line['description'] ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'material_cost' => $hpp['material_cost'],
                    'labor_cost' => $hpp['labor_cost'],
                    'overhead_cost' => $hpp['overhead_cost'],
                    'hpp_total' => $hpp['hpp_total'],
                    'profit_amount' => round($lineTotal - $hpp['hpp_total'], 2),
                ]);
            }

            $grandTotal = round($subtotal - (float) ($data['discount_amount'] ?? 0), 2);

            $order->update([
                'subtotal' => round($subtotal, 2),
                'hpp_total' => round($hppTotal, 2),
                'grand_total' => $grandTotal,
                'payment_status' => 'unpaid',
            ]);

            $order->statusHistories()->create([
                'status' => LaundryStatus::RECEIVED,
                'note' => 'Order diterima.',
                'changed_by' => $request->user()?->id,
                'changed_at' => Carbon::now(),
            ]);

            return $order;
        });

        $message = 'Order laundry berhasil dibuat.';
        if ($customerWasCreated) {
            $message .= ' Pelanggan baru otomatis dibuat.';
        }

        return redirect()->route('admin.laundry-orders.show', $laundryOrder)->with('success', $message);
    }

    public function show(LaundryOrder $laundryOrder): View
    {
        $laundryOrder->load([
            'customer',
            'items.servicePackage',
            'statusHistories.changer',
            'payments.receiver',
            'whatsappNotifications',
        ]);

        return view('admin.laundry-orders.show', [
            'laundryOrder' => $laundryOrder,
            'statusLabels' => LaundryStatus::labels(),
            'statuses' => LaundryStatus::values(),
        ]);
    }

    public function edit(LaundryOrder $laundryOrder): View
    {
        return view('admin.laundry-orders.edit', compact('laundryOrder'));
    }

    public function update(Request $request, LaundryOrder $laundryOrder): RedirectResponse
    {
        $data = $request->validate([
            'due_at' => ['nullable', 'date'],
            'status_note' => ['nullable', 'string'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $grandTotal = (float) $laundryOrder->subtotal - (float) $data['discount_amount'];

        $laundryOrder->update([
            'due_at' => $data['due_at'] ?? null,
            'status_note' => $data['status_note'] ?? null,
            'discount_amount' => $data['discount_amount'],
            'tax_amount' => 0,
            'grand_total' => round($grandTotal, 2),
            'updated_by' => $request->user()?->id,
        ]);

        $this->refreshPaymentStatus($laundryOrder);

        return redirect()->route('admin.laundry-orders.show', $laundryOrder)->with('success', 'Data order berhasil diperbarui.');
    }

    public function destroy(LaundryOrder $laundryOrder): RedirectResponse
    {
        if ($laundryOrder->status !== LaundryStatus::RECEIVED) {
            return back()->with('error', 'Order hanya bisa dihapus saat status masih Diterima.');
        }

        if ($laundryOrder->payments()->exists()) {
            return back()->with('error', 'Order tidak bisa dihapus karena sudah memiliki pembayaran.');
        }

        DB::transaction(function () use ($laundryOrder) {
            $movements = StockMovement::query()
                ->where('reference_type', LaundryOrder::class)
                ->where('reference_id', $laundryOrder->id)
                ->lockForUpdate()
                ->get();

            $stockReturns = [];
            foreach ($movements as $movement) {
                $itemId = (int) $movement->inventory_item_id;
                $returnQty = (float) $movement->quantity_out - (float) $movement->quantity_in;
                $stockReturns[$itemId] = ($stockReturns[$itemId] ?? 0.0) + $returnQty;
            }

            foreach ($stockReturns as $itemId => $returnQty) {
                if ($returnQty == 0.0) {
                    continue;
                }

                $item = InventoryItem::query()->lockForUpdate()->find($itemId);
                if (! $item) {
                    continue;
                }

                $item->current_stock = round((float) $item->current_stock + $returnQty, 3);
                $item->save();
            }

            StockMovement::query()
                ->where('reference_type', LaundryOrder::class)
                ->where('reference_id', $laundryOrder->id)
                ->delete();

            $laundryOrder->delete();
        });

        return redirect()->route('admin.laundry-orders.index')->with('success', 'Order berhasil dihapus.');
    }

    public function updateStatus(Request $request, LaundryOrder $laundryOrder): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(LaundryStatus::values())],
            'note' => ['nullable', 'string'],
        ]);

        if (! LaundryStatus::canTransition($laundryOrder->status, $data['status'])) {
            return back()->with('error', 'Perpindahan status tidak valid.');
        }

        $previousStatus = (string) $laundryOrder->status;
        $newStatus = (string) $data['status'];

        DB::transaction(function () use ($laundryOrder, $request, $data, $previousStatus, $newStatus) {
            $shouldConsumeStock = $this->shouldConsumeStockForTransition($previousStatus, $newStatus)
                && ! $this->hasUsageMovements($laundryOrder);

            if ($shouldConsumeStock) {
                $availability = $this->inventoryService->checkStockForOrder($laundryOrder, true);
                if (! $availability['is_available']) {
                    throw ValidationException::withMessages([
                        'stock' => $this->buildStockIssueMessages($availability['issues']),
                    ]);
                }

                $this->inventoryService->consumeMaterialsForOrder($laundryOrder, $request->user());
            }

            $laundryOrder->update([
                'status' => $newStatus,
                'status_note' => $data['note'] ?? null,
                'pickup_at' => $newStatus === LaundryStatus::PICKED_UP ? Carbon::now() : $laundryOrder->pickup_at,
                'updated_by' => $request->user()?->id,
            ]);

            $laundryOrder->statusHistories()->create([
                'status' => $newStatus,
                'note' => $data['note'] ?? null,
                'changed_by' => $request->user()?->id,
                'changed_at' => Carbon::now(),
            ]);
        });

        $statusLabel = LaundryStatus::labels()[$data['status']] ?? $data['status'];
        $waResult = $this->whatsappService->sendStatusUpdate($laundryOrder->fresh(['customer']), $statusLabel, $data['note'] ?? null);

        $response = back()->with('success', 'Status order berhasil diperbarui.');

        if (($waResult['sent'] ?? false) === false) {
            if (($waResult['skipped'] ?? false) === true) {
                $response->with('warning', 'Status berhasil diperbarui, notifikasi WhatsApp dilewati karena konfigurasi belum lengkap.');
            } else {
                $response->with('error', 'Status berhasil diperbarui, tetapi notifikasi WhatsApp gagal dikirim.');
            }
        }

        return $response;
    }

    public function addPayment(Request $request, LaundryOrder $laundryOrder): RedirectResponse
    {
        $data = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', Rule::in(['cash', 'transfer', 'qris', 'edc'])],
            'reference_no' => ['nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string'],
        ]);

        $outstanding = (float) $laundryOrder->grand_total - (float) $laundryOrder->paid_amount;
        if ((float) $data['amount'] > $outstanding && $outstanding > 0) {
            return back()->with('error', 'Nominal bayar melebihi sisa tagihan.');
        }

        $laundryOrder->payments()->create([
            'payment_date' => $data['payment_date'],
            'amount' => $data['amount'],
            'method' => $data['method'],
            'reference_no' => $data['reference_no'] ?? null,
            'note' => $data['note'] ?? null,
            'received_by' => $request->user()?->id,
        ]);

        $this->refreshPaymentStatus($laundryOrder);

        return back()->with('success', 'Pembayaran berhasil dicatat.');
    }

    /**
     * @param array<int, array<string, mixed>> $issues
     * @return array<int, string>
     */
    private function buildStockIssueMessages(array $issues): array
    {
        $messages = [];

        foreach ($issues as $issue) {
            $unitLabel = (($issue['pricing_unit'] ?? 'kg') === 'piece') ? 'item' : 'kg';
            $base = sprintf(
                'Baris %d (%s): diminta %s %s, maksimal %s %s berdasarkan stok saat ini.',
                (int) ($issue['line_no'] ?? 0),
                (string) ($issue['package_name'] ?? 'Paket'),
                $this->formatQty((float) ($issue['requested_qty'] ?? 0)),
                $unitLabel,
                $this->formatQty((float) ($issue['max_qty'] ?? 0)),
                $unitLabel,
            );

            $shortages = collect($issue['shortages'] ?? [])
                ->map(function (array $shortage) {
                    return sprintf(
                        '%s kurang %s %s',
                        (string) ($shortage['item_name'] ?? 'Material'),
                        $this->formatQty((float) ($shortage['shortage'] ?? 0)),
                        (string) ($shortage['unit'] ?? ''),
                    );
                })
                ->filter(fn (string $value) => trim($value) !== '')
                ->values()
                ->all();

            if (! empty($shortages)) {
                $base .= ' Kekurangan bahan: '.implode('; ', $shortages).'.';
            }

            $messages[] = $base;
        }

        return $messages;
    }

    private function formatQty(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }

    private function hasUsageMovements(LaundryOrder $laundryOrder): bool
    {
        return StockMovement::query()
            ->where('reference_type', LaundryOrder::class)
            ->where('reference_id', $laundryOrder->id)
            ->where('movement_type', 'usage')
            ->exists();
    }

    private function shouldConsumeStockForTransition(string $fromStatus, string $toStatus): bool
    {
        $workflowIndex = array_flip(LaundryStatus::workflow());

        if (! isset($workflowIndex[$fromStatus], $workflowIndex[$toStatus], $workflowIndex[LaundryStatus::WASHING])) {
            return false;
        }

        return $workflowIndex[$fromStatus] < $workflowIndex[LaundryStatus::WASHING]
            && $workflowIndex[$toStatus] >= $workflowIndex[LaundryStatus::WASHING];
    }

    private function refreshPaymentStatus(LaundryOrder $laundryOrder): void
    {
        $paidAmount = (float) Payment::query()->where('laundry_order_id', $laundryOrder->id)->sum('amount');
        $grandTotal = (float) $laundryOrder->fresh()->grand_total;

        $paymentStatus = 'unpaid';
        if ($paidAmount > 0 && $paidAmount < $grandTotal) {
            $paymentStatus = 'partial';
        }
        if ($paidAmount >= $grandTotal && $grandTotal > 0) {
            $paymentStatus = 'paid';
        }

        $laundryOrder->update([
            'paid_amount' => round($paidAmount, 2),
            'payment_status' => $paymentStatus,
        ]);
    }

    private function nextOrderNumber(): string
    {
        $prefix = 'ORD-'.Carbon::now()->format('Ymd');

        $counter = LaundryOrder::query()
            ->where('order_number', 'like', $prefix.'-%')
            ->count() + 1;

        return $prefix.'-'.str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
    }

    private function nextCustomerCode(): string
    {
        $nextId = (Customer::query()->max('id') ?? 0) + 1;

        return 'CUST-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    public function invoicePdf(LaundryOrder $laundryOrder): Response
    {
        $laundryOrder->load([
            'customer',
            'items.servicePackage',
            'payments',
            'statusHistories' => fn ($query) => $query->latest('changed_at'),
        ]);

        $pdf = Pdf::loadView('admin.laundry-orders.invoice-pdf', [
            'laundryOrder' => $laundryOrder,
            'statusLabels' => LaundryStatus::labels(),
            'qrDataUri' => $this->barcodeService->qrPngDataUri($laundryOrder->order_number, 180, 6),
        ])->setPaper('a4');

        return $pdf->download('invoice-'.$laundryOrder->order_number.'.pdf');
    }

    public function receiptCustomerPdf(LaundryOrder $laundryOrder): Response
    {
        $laundryOrder->load([
            'customer',
            'items.servicePackage',
            'payments',
        ]);

        $pdf = Pdf::loadView('admin.laundry-orders.receipt-customer-pdf', [
            'laundryOrder' => $laundryOrder,
            'statusLabels' => LaundryStatus::labels(),
            'qrDataUri' => $this->barcodeService->qrPngDataUri($laundryOrder->order_number, 220, 4),
        ])->setPaper([0, 0, 226.77, 620], 'portrait');

        return $pdf->download('struk-customer-'.$laundryOrder->order_number.'.pdf');
    }

    public function receiptInternalPdf(LaundryOrder $laundryOrder): Response
    {
        $laundryOrder->load([
            'customer',
            'items.servicePackage',
        ]);

        $pdf = Pdf::loadView('admin.laundry-orders.receipt-internal-pdf', [
            'laundryOrder' => $laundryOrder,
            'statusLabels' => LaundryStatus::labels(),
            'qrDataUri' => $this->barcodeService->qrPngDataUri($laundryOrder->order_number, 250, 4),
        ])->setPaper([0, 0, 226.77, 680], 'portrait');

        return $pdf->download('label-laundry-'.$laundryOrder->order_number.'.pdf');
    }
}
