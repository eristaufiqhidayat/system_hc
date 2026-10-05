<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DietVariant;
use App\Models\Menu;
use App\Models\MenuRotation;
use App\Services\MenuService;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function __construct(private MenuService $menus) {}

    public function index(Request $request): View
    {
        $grid = $this->menus->rotationGrid();
        [$week, $day] = array_map('intval', explode(',', $request->query('sel', '1,1')) + [1, 1]);
        $menu = $grid->get($week)?->get($day)?->menu ?? Menu::first();
        $menu?->load('recipeItems');

        return view('menus.index', [
            'grid' => $grid,
            'week' => $week,
            'day' => $day,
            'menu' => $menu,
            'variants' => DietVariant::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:menus,name'],
            'selling_price' => ['required', 'integer', 'min:0'],
            'week' => ['nullable', 'integer', 'between:1,4'],
            'weekday' => ['nullable', 'integer', 'between:1,5'],
            'items' => ['array'],
            'items.*.component' => ['required_with:items.*.cost', 'nullable', 'string', 'max:80'],
            'items.*.amount' => ['nullable', 'string', 'max:30'],
            'items.*.cost' => ['nullable', 'integer', 'min:0'],
        ]);

        $menu = DB::transaction(function () use ($data) {
            $menu = Menu::create(['name' => $data['name'], 'selling_price' => $data['selling_price']]);
            foreach ($data['items'] ?? [] as $item) {
                if (! empty($item['component'])) {
                    $menu->recipeItems()->create(['component' => $item['component'], 'amount' => $item['amount'] ?? '—', 'cost' => $item['cost'] ?? 0]);
                }
            }
            if (! empty($data['week']) && ! empty($data['weekday'])) {
                MenuRotation::updateOrCreate(['week' => $data['week'], 'weekday' => $data['weekday']], ['menu_id' => $menu->id]);
            }

            return $menu;
        });

        return redirect()->route('menus.index', isset($data['week'], $data['weekday']) ? ['sel' => $data['week'].','.$data['weekday']] : [])
            ->with('toast', 'Menu '.$menu->name.' ditambahkan');
    }

    public function announce(WhatsAppService $whatsapp): RedirectResponse
    {
        $nextWeek = today()->next('Monday');
        $days = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum'];
        $text = "Menu minggu depan HC Catering 🍱\n".$this->menus->weekMenus($nextWeek)->values()
            ->map(fn ($m, $i) => $days[$i].': '.$m->name)->implode("\n");

        $customers = Customer::whereHas('subscriptions', fn ($q) => $q->active())->get();
        $customers->each(fn (Customer $c) => $whatsapp->sendToCustomer($c, $text));

        return back()->with('toast', 'Menu minggu depan dikirim ke '.$customers->count().' pelanggan langganan');
    }

    public function toggleVariant(DietVariant $variant): RedirectResponse
    {
        $variant->update(['active' => ! $variant->active]);

        return back()->with('toast', 'Varian '.$variant->name.' '.($variant->active ? 'aktif' : 'nonaktif'));
    }
}
