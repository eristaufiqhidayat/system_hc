<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CourierAppController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MobilePreviewController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\PublicOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman publik (pelanggan)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:10,1');
});

Route::prefix('pesan')->name('shop.')->controller(PublicOrderController::class)->group(function () {
    Route::get('/', 'start')->name('start');
    Route::post('/', 'saveStart')->name('start.save');
    Route::get('/detail', 'details')->name('details');
    Route::post('/detail', 'saveDetails')->name('details.save');
    Route::get('/bayar', 'payment')->name('payment');
    Route::post('/bayar', 'confirm')->name('confirm');
    Route::get('/selesai/{order:code}', 'done')->name('done');
});

Route::prefix('akun/{token}')->name('portal.')->controller(CustomerPortalController::class)->group(function () {
    Route::get('/', 'show')->name('show');
    Route::post('/lewati', 'skip')->name('skip');
    Route::post('/jeda', 'pause')->name('pause');
    Route::post('/perpanjang', 'renew')->name('renew');
    Route::post('/preferensi', 'preferences')->name('preferences');
});

Route::post('/webhook/whatsapp', WhatsAppWebhookController::class)->name('webhook.whatsapp')->middleware('throttle:60,1');

/*
|--------------------------------------------------------------------------
| Sistem internal
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->middleware('akses:dashboard')->name('dashboard');
    Route::get('/cari', SearchController::class)->middleware('akses:dashboard')->name('search');

    Route::middleware('akses:orders')->group(function () {
        Route::get('/pesanan', [OrderController::class, 'index'])->name('orders.index');
        Route::post('/pesanan', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/pesanan/ekspor', [OrderController::class, 'export'])->name('orders.export');
        Route::get('/pesanan/{order:code}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/pesanan/{order:code}/maju', [OrderController::class, 'advance'])->name('orders.advance');
        Route::post('/pesanan/{order:code}/whatsapp', [OrderController::class, 'whatsapp'])->name('orders.whatsapp');
        Route::get('/pesanan/{order:code}/nota', [OrderController::class, 'receipt'])->name('orders.receipt');

        Route::get('/pengiriman', [DeliveryController::class, 'index'])->name('delivery.index');
        Route::patch('/pengiriman/{deliveryRoute}/kurir', [DeliveryController::class, 'assign'])->name('delivery.assign');
        Route::post('/pengiriman/{deliveryRoute}/optimalkan', [DeliveryController::class, 'optimize'])->name('delivery.optimize');
        Route::post('/pengiriman/kabari-pelanggan', [DeliveryController::class, 'notify'])->name('delivery.notify');

        Route::get('/langganan', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('/langganan/ingatkan', [SubscriptionController::class, 'remindAll'])->name('subscriptions.remind-all');
        Route::post('/langganan/{subscription}/ingatkan', [SubscriptionController::class, 'remind'])->name('subscriptions.remind');
        Route::post('/langganan/{subscription}/jeda', [SubscriptionController::class, 'pause'])->name('subscriptions.pause');
        Route::post('/langganan/{subscription}/lanjut', [SubscriptionController::class, 'resume'])->name('subscriptions.resume');

        Route::get('/kontrak', [ContractController::class, 'index'])->name('contracts.index');
        Route::post('/kontrak', [ContractController::class, 'store'])->name('contracts.store');
        Route::post('/kontrak/{contract}/diet', [ContractController::class, 'storeDiet'])->name('contracts.diet');
        Route::post('/kontrak/{contract}/invoice', [ContractController::class, 'issueInvoice'])->name('contracts.invoice');

        Route::get('/event', [EventController::class, 'index'])->name('events.index');
        Route::post('/event', [EventController::class, 'store'])->name('events.store');
        Route::post('/event/{event}/maju', [EventController::class, 'advance'])->name('events.advance');
        Route::post('/event/{event}/quotation', [EventController::class, 'quotation'])->name('events.quotation');
        Route::patch('/event/{event}/checklist/{item}', [EventController::class, 'toggleChecklist'])->name('events.checklist');

        Route::get('/pelanggan', [CustomerController::class, 'index'])->name('customers.index');
        Route::post('/pelanggan', [CustomerController::class, 'store'])->name('customers.store');
        Route::post('/pelanggan/promo', [CustomerController::class, 'promo'])->name('customers.promo');
        Route::get('/pelanggan/{customer}', [CustomerController::class, 'show'])->name('customers.show');

        Route::get('/chatbot', [ChatbotController::class, 'index'])->name('chatbot.index');
        Route::post('/chatbot/simulasi', [ChatbotController::class, 'simulate'])->name('chatbot.simulate');
        Route::post('/chatbot/status', [ChatbotController::class, 'toggle'])->name('chatbot.toggle');
        Route::put('/chatbot/aturan/{rule}', [ChatbotController::class, 'updateRule'])->name('chatbot.rules.update');
        Route::patch('/chatbot/terjadwal/{message}', [ChatbotController::class, 'toggleScheduled'])->name('chatbot.scheduled');

        Route::get('/web-pemesanan', [MobilePreviewController::class, 'shop'])->name('preview.shop');
        Route::get('/akun-pelanggan', [MobilePreviewController::class, 'portal'])->name('preview.portal');
    });

    Route::middleware('akses:production')->group(function () {
        Route::get('/produksi', [ProductionController::class, 'index'])->name('production.index');
        Route::post('/produksi/susun', [ProductionController::class, 'generate'])->name('production.generate');
        Route::patch('/produksi/{item}', [ProductionController::class, 'update'])->name('production.update');
        Route::get('/produksi/label', [ProductionController::class, 'labels'])->name('production.labels');

        Route::get('/stok', [StockController::class, 'index'])->name('stock.index');
        Route::post('/stok', [StockController::class, 'store'])->name('stock.store');
        Route::post('/stok/masuk', [StockController::class, 'stockIn'])->name('stock.in');
        Route::post('/stok/kirim-belanja', [StockController::class, 'sendList'])->name('stock.send-list');
        Route::post('/stok/po', [StockController::class, 'purchaseOrders'])->name('stock.po');
    });

    Route::middleware('akses:recipes')->group(function () {
        Route::get('/menu', [MenuController::class, 'index'])->name('menus.index');
        Route::post('/menu', [MenuController::class, 'store'])->name('menus.store');
        Route::post('/menu/umumkan', [MenuController::class, 'announce'])->name('menus.announce');
        Route::patch('/menu/varian/{variant}', [MenuController::class, 'toggleVariant'])->name('menus.variant');
    });

    Route::middleware('akses:billing')->group(function () {
        Route::get('/tagihan', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/tagihan/ekspor', [InvoiceController::class, 'export'])->name('invoices.export');
        Route::post('/tagihan/tagih', [InvoiceController::class, 'remindOverdue'])->name('invoices.remind');
        Route::post('/tagihan/{invoice}/lunas', [InvoiceController::class, 'markPaid'])->name('invoices.paid');
    });

    Route::middleware('akses:reports')->group(function () {
        Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/cetak', [ReportController::class, 'print'])->name('reports.print');
    });

    Route::middleware('akses:users')->group(function () {
        Route::get('/akses', [UserController::class, 'index'])->name('users.index');
        Route::post('/akses', [UserController::class, 'store'])->name('users.store');
        Route::patch('/akses/{user}', [UserController::class, 'update'])->name('users.update');
    });

    Route::middleware('akses:courier')->group(function () {
        Route::get('/aplikasi-kurir', [MobilePreviewController::class, 'courier'])->name('preview.courier');
        Route::get('/kurir', [CourierAppController::class, 'show'])->name('courier.app');
        Route::post('/kurir/titik/{stop}', [CourierAppController::class, 'deliver'])->name('courier.deliver');
    });
});
