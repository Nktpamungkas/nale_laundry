<?php

namespace App\Services;

use App\Models\LaundryOrder;
use App\Models\WhatsappNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class WhatsappService
{
    /**
     * @return array<string, mixed>
     */
    public function sendStatusUpdate(LaundryOrder $order, string $statusLabel, ?string $note = null): array
    {
        $isEnabled = (bool) config('services.whatsapp.enabled', false);
        $apiUrl = (string) config('services.whatsapp.api_url', '');
        $token = (string) config('services.whatsapp.token', '');
        $authMode = (string) config('services.whatsapp.auth_mode', 'body');
        $authHeader = (string) config('services.whatsapp.auth_header', 'Authorization');

        $customerPhone = $this->normalizePhone((string) $order->customer?->phone);

        if (! $isEnabled || $apiUrl === '' || $customerPhone === '') {
            $log = WhatsappNotification::query()->create([
                'laundry_order_id' => $order->id,
                'phone' => $customerPhone !== '' ? $customerPhone : '-',
                'event' => 'status_update',
                'message_text' => $this->buildStatusMessage($order, $statusLabel, $note),
                'request_payload' => null,
                'response_status' => null,
                'response_body' => null,
                'is_success' => false,
                'error_message' => 'Skipped: WhatsApp belum dikonfigurasi atau nomor pelanggan kosong.',
                'sent_at' => null,
            ]);

            return [
                'sent' => false,
                'skipped' => true,
                'log_id' => $log->id,
            ];
        }

        $payload = [
            config('services.whatsapp.field_phone', 'target') => $customerPhone,
            config('services.whatsapp.field_message', 'message') => $this->buildStatusMessage($order, $statusLabel, $note),
        ];

        $tokenField = (string) config('services.whatsapp.field_token', 'token');
        if ($token !== '' && $authMode !== 'header') {
            $payload[$tokenField] = $token;
        }

        try {
            $request = Http::asForm()->timeout((int) config('services.whatsapp.timeout', 10));
            if ($token !== '' && $authMode === 'header') {
                $request = $request->withHeaders([
                    $authHeader => $token,
                ]);
            }

            $response = $request->post($apiUrl, $payload);

            $isSuccess = $response->successful();

            $log = WhatsappNotification::query()->create([
                'laundry_order_id' => $order->id,
                'phone' => $customerPhone,
                'event' => 'status_update',
                'message_text' => $payload[config('services.whatsapp.field_message', 'message')],
                'request_payload' => $payload,
                'response_status' => $response->status(),
                'response_body' => $response->body(),
                'is_success' => $isSuccess,
                'error_message' => $isSuccess ? null : 'Gateway mengembalikan status HTTP gagal.',
                'sent_at' => $isSuccess ? Carbon::now() : null,
            ]);

            return [
                'sent' => $isSuccess,
                'skipped' => false,
                'log_id' => $log->id,
                'http_status' => $response->status(),
            ];
        } catch (\Throwable $exception) {
            $log = WhatsappNotification::query()->create([
                'laundry_order_id' => $order->id,
                'phone' => $customerPhone,
                'event' => 'status_update',
                'message_text' => $payload[config('services.whatsapp.field_message', 'message')],
                'request_payload' => $payload,
                'response_status' => null,
                'response_body' => null,
                'is_success' => false,
                'error_message' => $exception->getMessage(),
                'sent_at' => null,
            ]);

            return [
                'sent' => false,
                'skipped' => false,
                'log_id' => $log->id,
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function buildStatusMessage(LaundryOrder $order, string $statusLabel, ?string $note = null): string
    {
        $due = $order->due_at?->format('d/m/Y H:i') ?? '-';
        $customerName = $order->customer?->name ?? 'Pelanggan';

        $message = "Halo {$customerName}, status laundry Anda\n";
        $message .= "No Order: {$order->order_number}\n";
        $message .= "Status: {$statusLabel}\n";
        $message .= "Estimasi selesai: {$due}\n";
        $message .= "Tagihan: Rp ".number_format((float) $order->grand_total, 0, ',', '.')."\n";

        if ($note) {
            $message .= "Catatan: {$note}\n";
        }

        $message .= "- Nale Laundry";

        return $message;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '62')) {
            return '62'.$digits;
        }

        return $digits;
    }
}
