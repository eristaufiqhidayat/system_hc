<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        return view('search', [
            'q' => $q,
            'orders' => $q ? Order::with('customer')->search($q)->latest('id')->limit(20)->get() : collect(),
            'customers' => $q ? Customer::where('name', 'like', "%{$q}%")->orWhere('area', 'like', "%{$q}%")->limit(20)->get() : collect(),
            'invoices' => $q && $request->user()->canAccess('billing')
                ? Invoice::with('customer')->where('number', 'like', "%{$q}%")->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%"))->limit(20)->get()
                : collect(),
        ]);
    }
}
