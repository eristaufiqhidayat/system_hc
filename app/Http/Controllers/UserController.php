<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::orderByRaw("CASE role WHEN 'pemilik' THEN 0 WHEN 'admin' THEN 1 WHEN 'kepala_dapur' THEN 2 WHEN 'keuangan' THEN 3 ELSE 4 END")->orderBy('name')->get(),
            'roles' => User::ROLES,
            'permissions' => User::PERMISSIONS,
        ]);
    }

    public function store(Request $request, WhatsAppService $whatsapp): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        $password = Str::password(10, symbols: false);
        $user = User::create([...$data, 'password' => $password]);

        $whatsapp->send($user->phone, "Halo {$user->name}, akun Sistem HC Anda sudah dibuat.\nLogin: ".route('login')."\nEmail: {$user->email}\nKata sandi sementara: {$password}");

        return back()->with('toast', 'Undangan dikirim via WhatsApp ke '.$user->name);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'is_active' => ['boolean'],
        ]);

        if ($user->is($request->user()) && $data['role'] !== 'pemilik') {
            return back()->with('toast', 'Anda tidak bisa menurunkan peran akun sendiri');
        }

        $user->update(['role' => $data['role'], 'is_active' => $request->boolean('is_active', true)]);

        return back()->with('toast', 'Akses '.$user->name.' diperbarui');
    }
}
