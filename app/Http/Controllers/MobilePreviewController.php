<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\View\View;

/**
 * Halaman admin yang menampilkan aplikasi HP (web pemesanan, akun pelanggan, kurir) di dalam bingkai ponsel.
 */
class MobilePreviewController extends Controller
{
    public function shop(): View
    {
        return view('preview.shop');
    }

    public function portal(): View
    {
        $customer = Customer::whereHas('subscriptions')->orderBy('id')->firstOrFail();

        return view('preview.portal', compact('customer'));
    }

    public function courier(): View
    {
        return view('preview.courier');
    }
}
