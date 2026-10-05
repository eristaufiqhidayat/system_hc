<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoices) {}

    public function index(): View
    {
        $monthPayments = Payment::where('paid_at', '>=', now()->startOfMonth());
        $monthTotal = (int) (clone $monthPayments)->sum('amount');
        $qris = (int) (clone $monthPayments)->where('method', 'QRIS')->sum('amount');

        $unpaid = Invoice::unpaid()->get();
        $overdue = $unpaid->filter->isOverdue();

        $paidInvoices = Invoice::whereNotNull('paid_at')->get();
        $avgDays = $paidInvoices->isNotEmpty()
            ? (int) round($paidInvoices->avg(fn (Invoice $i) => $i->issued_at->diffInDays($i->paid_at)))
            : 0;

        return view('invoices.index', [
            'invoices' => Invoice::with('customer')->orderByRaw('paid_at IS NOT NULL')->orderBy('due_at')->get(),
            'monthTotal' => $monthTotal,
            'qrisPercent' => $monthTotal ? (int) round($qris / $monthTotal * 100) : 0,
            'outstanding' => (int) $unpaid->sum('amount'),
            'outstandingCount' => $unpaid->count(),
            'overdueAmount' => (int) $overdue->sum('amount'),
            'overdueCount' => $overdue->count(),
            'overdueDays' => (int) $overdue->max(fn (Invoice $i) => $i->due_at->diffInDays(today())),
            'avgDays' => $avgDays,
            'todayPayments' => Payment::with('customer')->whereDate('paid_at', today())->where('description', 'not like', 'Akumulasi%')->orderBy('paid_at')->get(),
        ]);
    }

    public function remindOverdue(): RedirectResponse
    {
        $count = $this->invoices->remindOverdue();

        return back()->with('toast', "Pengingat dikirim ke {$count} klien yang lewat jatuh tempo");
    }

    public function markPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate(['method' => ['nullable', 'string', 'max:40']]);
        $this->invoices->markPaid($invoice, $data['method'] ?? 'Transfer');

        return back()->with('toast', "Invoice {$invoice->number} ditandai lunas");
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['No. invoice', 'Klien', 'Periode', 'Jumlah', 'Terbit', 'Jatuh tempo', 'Status']);
            foreach (Invoice::with('customer')->orderBy('due_at')->get() as $i) {
                fputcsv($out, [$i->number, $i->client_label, $i->period, $i->amount, $i->issued_at->toDateString(), $i->due_at->toDateString(), $i->state[0]]);
            }
            fclose($out);
        }, 'piutang-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }
}
