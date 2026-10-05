<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pengiriman pesan WhatsApp.
 *
 * Bila WHATSAPP_API_URL diisi, pesan diteruskan ke gateway (format JSON {to, message}).
 * Tanpa konfigurasi, pesan hanya dicatat di tabel whatsapp_messages dan log aplikasi.
 */
class WhatsAppService
{
    public function send(?string $phone, string $message, ?Customer $customer = null): WhatsappMessage
    {
        $phone ??= $customer?->whatsapp;

        $record = WhatsappMessage::create([
            'customer_id' => $customer?->id,
            'phone' => $phone,
            'direction' => 'out',
            'body' => $message,
        ]);

        $url = config('services.whatsapp.url');
        if ($url && $phone) {
            try {
                Http::withToken((string) config('services.whatsapp.token'))
                    ->timeout(10)
                    ->post($url, ['to' => $phone, 'message' => $message])
                    ->throw();
            } catch (Throwable $e) {
                Log::warning('Gagal mengirim WhatsApp', ['phone' => $phone, 'error' => $e->getMessage()]);
            }
        } else {
            Log::info('WhatsApp (simulasi)', ['phone' => $phone, 'message' => $message]);
        }

        return $record;
    }

    public function sendToCustomer(Customer $customer, string $message): WhatsappMessage
    {
        return $this->send($customer->whatsapp, $message, $customer);
    }
}
