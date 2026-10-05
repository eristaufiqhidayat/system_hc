<?php

namespace Tests\Feature;

use App\Models\CateringEvent;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\DeliveryStop;
use App\Models\Ingredient;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function owner(): User
    {
        return User::where('role', 'pemilik')->first();
    }

    public function test_admin_creates_and_advances_an_order(): void
    {
        $this->actingAs($this->owner())->post('/pesanan', [
            'customer' => 'Ibu Baru',
            'business_line' => 'Nasi box',
            'portions' => 25,
            'delivery_date' => today()->addDays(2)->toDateString(),
            'delivery_time' => '11.30',
            'address' => 'Jl. Melati 1',
            'payment' => 'qris',
        ])->assertRedirect()->assertSessionHas('toast');

        $order = Order::where('code', 'HC-2292')->firstOrFail();
        $this->assertSame('Baru', $order->status);
        $this->assertTrue(Customer::where('name', 'Ibu Baru')->exists());

        $this->post("/pesanan/{$order->code}/maju")->assertRedirect();
        $this->assertSame('Dikonfirmasi', $order->fresh()->status);
        $this->assertCount(2, $order->histories);

        $this->get("/pesanan/{$order->code}", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee('Tandai diproses');
    }

    public function test_public_shop_flow_creates_paid_order_and_subscription(): void
    {
        $this->post('/pesan', ['service' => 'Rantangan', 'package' => 'Mingguan', 'portions' => 3, 'action' => 'next'])
            ->assertRedirect('/pesan/detail');
        $this->post('/pesan/detail', [
            'name' => 'Pak Budi',
            'whatsapp' => '0812-1111-2222',
            'address' => 'Jl. Kenanga 5, Bintaro',
            'start_date' => today()->addDays(2)->toDateString(),
            'window' => '11.00–12.00',
            'preferences' => ['Tidak pedas'],
        ])->assertRedirect('/pesan/bayar');
        $this->get('/pesan/bayar')->assertOk()->assertSee('Rp 412.500');

        $response = $this->post('/pesan/bayar');
        $order = Order::latest('id')->first();
        $response->assertRedirect(route('shop.done', $order));

        $this->assertSame('lunas', $order->payment_status);
        $this->assertSame('Dikonfirmasi', $order->status);
        $this->assertSame(412500, $order->total);
        $this->assertTrue(Subscription::whereHas('customer', fn ($q) => $q->where('name', 'Pak Budi'))->exists());
    }

    public function test_customer_portal_skip_pause_and_renew(): void
    {
        $customer = Customer::where('name', 'Ibu Rina')->first();
        $sub = $customer->subscriptions()->first();
        $base = '/akun/'.$customer->portal_token;

        $this->post($base.'/lewati', ['date' => today()->next('Monday')->toDateString()])->assertRedirect();
        $this->assertSame(1, $sub->skips()->count());

        $this->post($base.'/jeda')->assertRedirect();
        $this->assertTrue($sub->fresh()->isPaused());

        $before = $sub->fresh()->remaining_days;
        $this->post($base.'/perpanjang')->assertRedirect();
        $this->assertSame($before + 20, $sub->fresh()->remaining_days);
    }

    public function test_courier_marks_stop_delivered(): void
    {
        $courier = User::where('name', 'Rudi')->first();
        $stop = DeliveryStop::whereHas('route', fn ($q) => $q->where('courier_id', $courier->id))->whereNull('delivered_at')->orderBy('sequence')->first();

        $this->actingAs($courier)->post("/kurir/titik/{$stop->id}")->assertRedirect('/kurir');
        $this->assertNotNull($stop->fresh()->delivered_at);

        $other = DeliveryStop::whereHas('route', fn ($q) => $q->where('courier_id', '!=', $courier->id))->whereNull('delivered_at')->first();
        $this->actingAs($courier)->post("/kurir/titik/{$other->id}")->assertForbidden();
    }

    public function test_contract_invoice_and_payment(): void
    {
        $contract = Contract::whereHas('customer', fn ($q) => $q->where('name', 'Kantor C'))->first();

        $this->actingAs($this->owner())->post("/kontrak/{$contract->id}/invoice")->assertRedirect();
        $invoice = $contract->invoices()->latest('id')->first();
        $this->assertGreaterThan(0, $invoice->amount);

        $this->post("/tagihan/{$invoice->id}/lunas")->assertRedirect();
        $this->assertNotNull($invoice->fresh()->paid_at);
        $this->assertSame(1, $invoice->payments()->count());
    }

    public function test_overdue_reminder_and_stock_in(): void
    {
        $this->actingAs($this->owner())->post('/tagihan/tagih')->assertSessionHas('toast', 'Pengingat dikirim ke 2 klien yang lewat jatuh tempo');
        $this->assertSame(2, Invoice::overdue()->whereNotNull('last_reminded_at')->count());

        $chicken = Ingredient::where('name', 'Dada & paha ayam')->first();
        $this->post('/stok/masuk', ['ingredient_id' => $chicken->id, 'type' => 'in', 'quantity' => 30, 'unit_price' => 43000])->assertRedirect();
        $this->assertEquals(68, $chicken->fresh()->stock);
        $this->assertSame(43000, $chicken->fresh()->last_price);
    }

    public function test_event_moves_through_pipeline(): void
    {
        $event = CateringEvent::where('stage', 0)->first();

        $this->actingAs($this->owner())->post("/event/{$event->id}/maju")->assertRedirect();
        $this->assertSame(1, $event->fresh()->stage);
        $this->assertTrue(Invoice::where('catering_event_id', $event->id)->exists());
    }

    public function test_chatbot_answers_keywords(): void
    {
        $this->actingAs($this->owner())->postJson('/chatbot/simulasi', ['message' => 'menu minggu ini apa?'])
            ->assertOk()->assertJsonPath('forwarded', false)->assertJsonFragment(['button' => '🛒 Buka halaman pesan']);

        $this->postJson('/chatbot/simulasi', ['message' => 'halo'])
            ->assertOk()->assertJsonPath('forwarded', true);
    }

    public function test_production_rekap_can_be_regenerated(): void
    {
        $this->actingAs($this->owner())->post('/produksi/susun')->assertRedirect()->assertSessionHas('toast');
        $this->get('/produksi')->assertOk()->assertSee('Sayur asem / bening');
    }
}
