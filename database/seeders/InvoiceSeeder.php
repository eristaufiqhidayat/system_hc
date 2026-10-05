<?php

namespace Database\Seeders;

use App\Models\CateringEvent;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceService;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /** Omzet bulanan (juta rupiah) 6 bulan terakhir, bulan berjalan paling akhir. */
    private const MONTHLY_REVENUE = [182, 195, 188, 210, 226, 241];

    public function run(InvoiceService $service): void
    {
        Payment::query()->delete();
        Invoice::query()->delete();

        $lastMonth = today()->subMonthNoOverflow()->startOfMonth();
        $twoMonthsAgo = today()->subMonthsNoOverflow(2)->startOfMonth();
        $period = fn ($m) => month_id($m->month, false).' '.$m->year;

        // Invoice kontrak bulan lalu: belum jatuh tempo.
        foreach (['RS B' => 9, 'Kantor A' => 4] as $name => $term) {
            $contract = Contract::whereHas('customer', fn ($q) => $q->where('name', $name))->firstOrFail();
            Invoice::create([
                'number' => $service->nextNumber($lastMonth),
                'customer_id' => $contract->customer_id,
                'contract_id' => $contract->id,
                'period' => $period($lastMonth),
                'amount' => $service->contractRecap($contract, $lastMonth)['amount'],
                'issued_at' => today()->startOfMonth(),
                'due_at' => today()->addDays($term),
            ]);
        }

        // Invoice 2 bulan lalu: dua lewat jatuh tempo, satu lunas.
        foreach (['Pabrik E' => false, 'Kantor C' => true, 'Klinik D' => false] as $name => $paid) {
            $contract = Contract::whereHas('customer', fn ($q) => $q->where('name', $name))->firstOrFail();
            $invoice = Invoice::create([
                'number' => $service->nextNumber($twoMonthsAgo),
                'customer_id' => $contract->customer_id,
                'contract_id' => $contract->id,
                'period' => $period($twoMonthsAgo),
                'amount' => $contract->daily_portions * 26 * $contract->price_per_portion,
                'issued_at' => $lastMonth->copy(),
                'due_at' => $lastMonth->copy()->addDays(9),
            ]);
            if ($paid) {
                $invoice->update(['paid_at' => $lastMonth->copy()->addDays(8)->setTime(10, 0)]);
                Payment::create([
                    'customer_id' => $invoice->customer_id, 'invoice_id' => $invoice->id, 'method' => 'Transfer',
                    'amount' => $invoice->amount, 'description' => 'Pembayaran '.$invoice->number, 'paid_at' => $invoice->paid_at,
                ]);
            }
        }

        // Invoice event Bpk. Hendra: DP lunas, pelunasan menunggu.
        $event = CateringEvent::whereHas('customer', fn ($q) => $q->where('name', 'Bpk. Hendra'))->firstOrFail();
        $dp = $service->issueForEvent($event, 'dp');
        $dp->update(['issued_at' => today()->subDays(6), 'due_at' => today()->subDays(3), 'paid_at' => today()->subDays(3)->setTime(14, 0)]);
        Payment::create([
            'customer_id' => $event->customer_id, 'invoice_id' => $dp->id, 'method' => 'Transfer',
            'amount' => $dp->amount, 'description' => 'DP 50% '.$event->name, 'paid_at' => $dp->paid_at,
        ]);
        $service->issueForEvent($event, 'pelunasan');

        // Pembayaran masuk hari ini.
        $today = [
            ['Ibu Rina', 'QRIS', '08:12', 'Rantangan bulanan', 1000000],
            ['Ibu Maya', 'Transfer BCA', '09:40', 'Nasi box 35', 1050000],
            ['Bpk. Arif', 'QRIS', '10:05', 'Rantangan harian', 30000],
            ['Keluarga Wijaya', 'QRIS', '11:21', 'Perpanjang bulanan', 2000000],
        ];
        foreach ($today as [$name, $method, $time, $desc, $amount]) {
            Payment::create([
                'customer_id' => Customer::where('name', $name)->value('id'),
                'method' => $method,
                'amount' => $amount,
                'description' => $desc,
                'paid_at' => today()->setTimeFromTimeString($time),
            ]);
        }

        // Akumulasi pembayaran harian per bulan agar grafik omzet terisi.
        $filler = Customer::where('segment', 'Rantangan')->first();
        foreach (self::MONTHLY_REVENUE as $i => $million) {
            $month = today()->subMonthsNoOverflow(count(self::MONTHLY_REVENUE) - 1 - $i)->startOfMonth();
            $existing = Payment::whereBetween('paid_at', [$month, $month->copy()->endOfMonth()])->sum('amount');
            $rest = max(0, $million * 1_000_000 - $existing);
            $day = $month->isSameMonth(today()) ? today()->subDay()->max($month) : $month->copy()->addDays(14);

            Payment::create(['customer_id' => $filler->id, 'method' => 'QRIS', 'amount' => (int) round($rest * 0.38), 'description' => 'Akumulasi pembayaran QRIS', 'paid_at' => $day->copy()->setTime(20, 0)]);
            Payment::create(['customer_id' => $filler->id, 'method' => 'Transfer', 'amount' => (int) round($rest * 0.62), 'description' => 'Akumulasi pembayaran transfer', 'paid_at' => $day->copy()->setTime(20, 5)]);
        }
    }
}
