<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $segment = $request->query('segmen');
        $customers = Customer::withCount(['orders', 'contracts'])
            ->withMax('orders', 'delivery_date')
            ->when($segment, fn ($q) => $q->where('segment', $segment))
            ->orderByRaw("CASE value_tier WHEN 'Tinggi' THEN 0 WHEN 'Sedang' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $total = Customer::count();
        $returning = Customer::has('orders', '>', 1)->orHas('contracts')->orHas('subscriptions')->count();
        $avgOrder = (int) Order::where('total', '>', 0)->where('channel', '!=', 'Kontrak')->avg('total');
        $inactive = Customer::whereDoesntHave('orders', fn ($q) => $q->where('delivery_date', '>=', now()->subDays(60)))
            ->doesntHave('contracts')->doesntHave('subscriptions')->count();

        return view('customers.index', [
            'customers' => $customers,
            'segment' => $segment,
            'total' => $total,
            'newThisMonth' => Customer::where('created_at', '>=', now()->startOfMonth())->count(),
            'returningPercent' => $total ? (int) round($returning / $total * 100) : 0,
            'avgOrder' => $avgOrder,
            'inactive' => $inactive,
        ]);
    }

    public function show(Request $request, Customer $customer): View|RedirectResponse
    {
        if (! $request->ajax()) {
            return redirect()->route('customers.index');
        }

        $customer->loadCount(['orders', 'contracts']);

        return view('customers._drawer', [
            'customer' => $customer,
            'orders' => $customer->orders()->latest('delivery_date')->limit(5)->get(),
            'lastOrder' => $customer->orders()->latest('delivery_date')->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:customers,name'],
            'segment' => ['required', Rule::in(Customer::SEGMENTS)],
            'area' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'preference' => ['nullable', 'string', 'max:200'],
            'birthday' => ['nullable', 'date'],
        ]);

        Customer::create($data);

        return back()->with('toast', 'Pelanggan '.$data['name'].' ditambahkan');
    }

    public function promo(Request $request, WhatsAppService $whatsapp): RedirectResponse
    {
        $data = $request->validate([
            'segment' => ['nullable', Rule::in(Customer::SEGMENTS)],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $customers = Customer::whereNotNull('whatsapp')
            ->when($data['segment'] ?? null, fn ($q, $s) => $q->where('segment', $s))
            ->get();
        $customers->each(fn (Customer $c) => $whatsapp->sendToCustomer($c, $data['message']));

        return back()->with('toast', 'Promo WhatsApp dikirim ke '.$customers->count().' pelanggan');
    }
}
