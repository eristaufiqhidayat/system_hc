<?php

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint untuk gateway WhatsApp Business: menerima pesan masuk, mengembalikan balasan bot.
 * Lindungi dengan WHATSAPP_WEBHOOK_SECRET (header X-Webhook-Secret).
 */
class WhatsAppWebhookController extends Controller
{
    public function __invoke(Request $request, ChatbotService $bot): JsonResponse
    {
        $secret = config('services.whatsapp.webhook_secret');
        abort_if($secret && ! hash_equals($secret, (string) $request->header('X-Webhook-Secret')), 401);

        $data = $request->validate([
            'from' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        if (! $bot->isActive()) {
            return response()->json(['reply' => null, 'forwarded' => true]);
        }

        return response()->json($bot->reply($data['message'], $data['from']));
    }
}
