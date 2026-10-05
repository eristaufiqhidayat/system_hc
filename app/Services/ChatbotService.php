<?php

namespace App\Services;

use App\Models\ChatbotRule;
use App\Models\Setting;
use App\Models\WhatsappMessage;

class ChatbotService
{
    public function __construct(private MenuService $menus) {}

    public const FALLBACK = 'Terima kasih! Pesan Anda sudah kami teruskan ke admin, akan dibalas segera 🙏';

    public function isActive(): bool
    {
        return (bool) Setting::get('chatbot_active', '1');
    }

    /**
     * Cari balasan otomatis berdasarkan kata kunci.
     *
     * @return array{reply:string, button:?string, forwarded:bool}
     */
    public function reply(string $message, ?string $phone = null): array
    {
        $text = mb_strtolower($message);

        $rule = ChatbotRule::orderByDesc('priority')->get()
            ->first(fn (ChatbotRule $r) => collect($r->keywordList())->contains(fn ($k) => $k !== '' && str_contains($text, $k)));

        $result = $rule
            ? ['reply' => $this->render($rule->response), 'button' => $rule->quick_button, 'forwarded' => $rule->forward_to_admin]
            : ['reply' => self::FALLBACK, 'button' => null, 'forwarded' => true];

        WhatsappMessage::create(['phone' => $phone, 'direction' => 'in', 'body' => $message, 'handled_by_bot' => ! $result['forwarded']]);
        WhatsappMessage::create(['phone' => $phone, 'direction' => 'out', 'body' => $result['reply'], 'handled_by_bot' => true]);

        return $result;
    }

    private function render(string $template): string
    {
        if (! str_contains($template, '{menu_minggu_ini}')) {
            return $template;
        }

        $days = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum'];
        $menu = $this->menus->weekMenus(today())->values()
            ->map(fn ($m, $i) => ($days[$i] ?? '').': '.$m->name)
            ->implode("\n");

        return str_replace('{menu_minggu_ini}', $menu, $template);
    }

    /**
     * @return array{chats:int, auto_percent:int}
     */
    public function weeklyStats(): array
    {
        $incoming = WhatsappMessage::where('direction', 'in')->where('created_at', '>=', now()->subWeek());
        $total = (clone $incoming)->count();
        $auto = (clone $incoming)->where('handled_by_bot', true)->count();

        return [
            'chats' => $total,
            'auto_percent' => $total ? (int) round($auto / $total * 100) : 0,
        ];
    }
}
