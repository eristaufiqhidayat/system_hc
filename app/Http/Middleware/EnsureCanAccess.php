<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi halaman sesuai matriks hak akses per peran (User::PERMISSIONS).
 */
class EnsureCanAccess
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            return redirect()->route('login');
        }

        if (! $user->canAccess($permission)) {
            if ($permission === 'dashboard' && $user->canAccess('courier')) {
                return redirect()->route('courier.app');
            }

            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
