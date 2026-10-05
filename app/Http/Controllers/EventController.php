<?php

namespace App\Http\Controllers;

use App\Models\CateringEvent;
use App\Models\Customer;
use App\Models\EventChecklistItem;
use App\Services\InvoiceService;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $events = CateringEvent::with('customer')->orderBy('event_date')->get();
        $selected = $events->firstWhere('id', (int) $request->query('event'))
            ?? $events->first(fn ($e) => $e->stage === 2) ?? $events->first();
        $selected?->load('checklist');

        return view('events.index', [
            'columns' => CateringEvent::STAGES,
            'events' => $events->groupBy('stage'),
            'selected' => $selected,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'customer' => ['required', 'string', 'max:120'],
            'venue_area' => ['nullable', 'string', 'max:120'],
            'pax' => ['required', 'integer', 'min:10'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'event_time' => ['nullable', 'string', 'max:10'],
            'price_per_pax' => ['nullable', 'integer', 'min:0'],
        ]);

        $event = DB::transaction(function () use ($data) {
            $customer = Customer::firstOrCreate(['name' => $data['customer']], ['segment' => 'Event', 'area' => $data['venue_area'] ?? null]);
            $event = CateringEvent::create([
                ...collect($data)->except('customer')->all(),
                'customer_id' => $customer->id,
                'price_per_pax' => $data['price_per_pax'] ?? 0,
                'stage' => 0,
                'menu' => CateringEvent::DEFAULT_MENU,
            ]);
            foreach (CateringEvent::DEFAULT_CHECKLIST as $i => $label) {
                $event->checklist()->create(['label' => $label, 'sort' => $i]);
            }

            return $event;
        });

        return redirect()->route('events.index', ['event' => $event->id])->with('toast', 'Permintaan event '.$event->name.' tersimpan');
    }

    public function advance(CateringEvent $event, InvoiceService $invoices): RedirectResponse
    {
        if ($event->stage >= count(CateringEvent::STAGES) - 1) {
            return back();
        }

        $event->stage++;
        if ($event->stage === 2) {
            $event->dp_received = true;
        }
        $event->save();

        if ($event->stage === 1 && ! $event->customer->invoices()->where('catering_event_id', $event->id)->exists()) {
            $invoices->issueForEvent($event, 'dp');
        }
        if ($event->stage === 3 && $event->customer->invoices()->where('catering_event_id', $event->id)->count() < 2) {
            $invoices->issueForEvent($event, 'pelunasan');
        }

        return redirect()->route('events.index', ['event' => $event->id])
            ->with('toast', $event->name.' dipindah ke “'.$event->stage_label.'”');
    }

    public function quotation(CateringEvent $event, WhatsAppService $whatsapp): RedirectResponse
    {
        $whatsapp->sendToCustomer($event->customer, sprintf(
            "Quotation %s (%d pax, %s):\nHarga per pax %s\nSewa peralatan %s\nTotal %s\nDP 50%% untuk mengunci tanggal.",
            $event->name, $event->pax, date_id($event->event_date, true),
            rupiah($event->price_per_pax), rupiah($event->equipment_cost), rupiah($event->total),
        ));

        return back()->with('toast', 'Quotation dikirim ke WhatsApp klien');
    }

    public function toggleChecklist(CateringEvent $event, EventChecklistItem $item): RedirectResponse
    {
        abort_unless($item->catering_event_id === $event->id, 404);
        $item->update(['done' => ! $item->done]);

        return redirect()->route('events.index', ['event' => $event->id]);
    }
}
