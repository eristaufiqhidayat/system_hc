<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\PatientDiet;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function __construct(private InvoiceService $invoices) {}

    public function index(Request $request): View
    {
        $contracts = Contract::with('customer')->orderByDesc('daily_portions')->get();
        $selected = $contracts->firstWhere('id', (int) $request->query('klien')) ?? $contracts->first();
        $selected?->load('shifts', 'diets');

        $month = today()->subMonthNoOverflow()->startOfMonth();

        return view('contracts.index', [
            'contracts' => $contracts,
            'selected' => $selected,
            'month' => $month,
            'recap' => $selected ? $this->invoices->contractRecap($selected, $month) : null,
            'invoice' => $selected?->invoices()->where('period', month_id($month->month, false).' '.$month->year)->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'area' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(Contract::TYPES)],
            'daily_portions' => ['required', 'integer', 'min:0'],
            'price_per_portion' => ['required', 'integer', 'min:0'],
            'delivery_info' => ['nullable', 'string', 'max:200'],
            'pic' => ['nullable', 'string', 'max:120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $contract = DB::transaction(function () use ($data) {
            $customer = Customer::firstOrCreate(['name' => $data['name']], ['segment' => $data['type'], 'area' => $data['area'], 'value_tier' => 'Tinggi']);

            return Contract::create([
                'customer_id' => $customer->id,
                'type' => $data['type'],
                'summary' => $data['daily_portions'].' porsi/hari',
                'daily_portions' => $data['daily_portions'],
                'price_per_portion' => $data['price_per_portion'],
                'delivery_info' => $data['delivery_info'] ?? null,
                'pic' => $data['pic'] ?? null,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
            ]);
        });

        return redirect()->route('contracts.index', ['klien' => $contract->id])->with('toast', 'Kontrak '.$data['name'].' tersimpan');
    }

    public function storeDiet(Request $request, Contract $contract): RedirectResponse
    {
        $data = $request->validate([
            'room' => ['required', 'string', 'max:20'],
            'diet_type' => ['required', 'string', 'max:60'],
            'note' => ['nullable', 'string', 'max:200'],
            'valid_until' => ['nullable', 'date'],
        ]);

        PatientDiet::updateOrCreate(
            ['contract_id' => $contract->id, 'room' => $data['room']],
            [...$data, 'is_new' => true],
        );

        return redirect()->route('contracts.index', ['klien' => $contract->id])->with('toast', 'Diet kamar '.$data['room'].' diperbarui & masuk rekap dapur');
    }

    public function issueInvoice(Contract $contract): RedirectResponse
    {
        $month = today()->subMonthNoOverflow()->startOfMonth();
        $invoice = $this->invoices->issueForContract($contract->load('customer'), $month);

        return redirect()->route('contracts.index', ['klien' => $contract->id])
            ->with('toast', "Invoice {$invoice->number} untuk {$contract->customer->name} diterbitkan & dikirim");
    }
}
