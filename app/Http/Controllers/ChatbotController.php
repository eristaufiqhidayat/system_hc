<?php

namespace App\Http\Controllers;

use App\Models\ChatbotRule;
use App\Models\Order;
use App\Models\ScheduledMessage;
use App\Models\Setting;
use App\Models\Subscription;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatbotController extends Controller
{
    public function __construct(private ChatbotService $bot) {}

    public function index(): View
    {
        $weekOrders = Order::where('channel', 'WA bot')->where('created_at', '>=', now()->subWeek());

        return view('chatbot.index', [
            'active' => $this->bot->isActive(),
            'stats' => $this->bot->weeklyStats(),
            'botOrders' => (clone $weekOrders)->count(),
            'botRevenue' => (int) (clone $weekOrders)->sum('total'),
            'rules' => ChatbotRule::orderByDesc('priority')->get()->sortBy(fn ($r) => $r->forward_to_admin && $r->priority >= 100 ? 1 : 0)->values(),
            'scheduled' => ScheduledMessage::orderBy('id')->get(),
            'subscribers' => Subscription::active()->count(),
        ]);
    }

    public function simulate(Request $request): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:500']]);

        return response()->json($this->bot->reply($data['message']));
    }

    public function toggle(): RedirectResponse
    {
        $active = ! $this->bot->isActive();
        Setting::put('chatbot_active', $active ? '1' : '0');

        return back()->with('toast', $active ? 'Chatbot diaktifkan' : 'Chatbot dimatikan, semua chat diteruskan ke admin');
    }

    public function updateRule(Request $request, ChatbotRule $rule): RedirectResponse
    {
        $data = $request->validate([
            'keywords' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:200'],
            'response' => ['required', 'string', 'max:2000'],
            'quick_button' => ['nullable', 'string', 'max:60'],
            'forward_to_admin' => ['boolean'],
        ]);

        $rule->update([...$data, 'forward_to_admin' => $request->boolean('forward_to_admin')]);

        return back()->with('toast', 'Balasan otomatis “'.$rule->keywords.'” disimpan');
    }

    public function toggleScheduled(ScheduledMessage $message): RedirectResponse
    {
        $message->update(['active' => ! $message->active]);

        return back()->with('toast', $message->name.' '.($message->active ? 'aktif' : 'nonaktif'));
    }
}
