<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public static function adminPages(): array
    {
        return array_map(fn ($p) => [$p], [
            '/', '/pesanan', '/produksi', '/pengiriman', '/stok', '/langganan', '/kontrak', '/event',
            '/pelanggan', '/tagihan', '/laporan', '/menu', '/chatbot', '/akses', '/web-pemesanan',
            '/akun-pelanggan', '/aplikasi-kurir', '/kurir', '/cari?q=rina', '/produksi/label', '/laporan/cetak',
            '/pesanan/HC-2291/nota', '/pesanan?status=Baru&lini=Rantangan', '/langganan?filter=Dijeda', '/laporan?periode=bulan',
        ]);
    }

    #[DataProvider('adminPages')]
    public function test_owner_can_open_every_page(string $url): void
    {
        $owner = User::where('role', 'pemilik')->first();

        $this->actingAs($owner)->get($url)->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_login_works(): void
    {
        $this->post('/login', ['email' => 'owner@hccatering.test', 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_public_pages_are_reachable_without_login(): void
    {
        $this->get('/pesan')->assertOk()->assertSee('Pilih paket');
        $token = Customer::where('name', 'Ibu Rina')->value('portal_token');
        $this->get('/akun/'.$token)->assertOk()->assertSee('Halo, Ibu Rina');
    }

    public function test_roles_are_restricted(): void
    {
        $kitchen = User::where('role', 'kepala_dapur')->first();
        $this->actingAs($kitchen)->get('/produksi')->assertOk();
        $this->actingAs($kitchen)->get('/tagihan')->assertForbidden();

        $finance = User::where('role', 'keuangan')->first();
        $this->actingAs($finance)->get('/tagihan')->assertOk();
        $this->actingAs($finance)->get('/pesanan')->assertForbidden();

        $courier = User::where('name', 'Rudi')->first();
        $this->actingAs($courier)->get('/')->assertRedirect(route('courier.app'));
        $this->actingAs($courier)->get('/kurir')->assertOk()->assertSee('BSD');
    }
}
