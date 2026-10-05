<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request): View
    {
        $status = $request->query('status', 'Semua');
        $line = $request->query('lini', 'Semua');
        $q = $request->query('q');

        $orders = Order::with('customer')
            ->when($status !== 'Semua', fn ($query) => $query->where('status', $status))
            ->when($line !== 'Semua', fn ($query) => $query->where('business_line', $line))
            ->search($q)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $counts = Order::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('orders.index', compact('orders', 'status', 'line', 'q', 'counts'));
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = $this->orders->create([...$request->validated(), 'channel' => 'Admin'], $request->user());

        return redirect()->back()->with('toast', "Pesanan #{$order->code} tersimpan & masuk rekap dapur");
    }

    public function show(Request $request, Order $order): View|RedirectResponse
    {
        $order->load('customer', 'histories');

        if (! $request->ajax()) {
            return redirect()->route('orders.index', ['buka' => $order->code]);
        }

        return view('orders._drawer', compact('order'));
    }

    public function advance(Request $request, Order $order): RedirectResponse
    {
        $this->orders->advance($order, $request->user());

        return back()->with('toast', "#{$order->code} ditandai ".mb_strtolower($order->status))
            ->with('open_drawer', route('orders.show', $order));
    }

    public function whatsapp(Order $order): RedirectResponse
    {
        $this->orders->sendConfirmation($order);

        return back()->with('toast', 'Pesan konfirmasi dikirim ke WhatsApp pelanggan');
    }

    public function receipt(Order $order): View
    {
        return view('orders.receipt', ['order' => $order->load('customer')]);
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['No', 'Pelanggan', 'Area', 'Lini', 'Item', 'Porsi', 'Jadwal', 'Masuk via', 'Pembayaran', 'Status', 'Total']);
            Order::with('customer')->latest('id')->chunk(200, function ($orders) use ($out) {
                foreach ($orders as $o) {
                    fputcsv($out, [$o->code, $o->customer->name, $o->area, $o->business_line, $o->item, $o->portions, $o->schedule_label, $o->channel, $o->payment_label, $o->status, $o->total]);
                }
            });
            fclose($out);
        }, 'pesanan-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }
}
