<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi rute berdasarkan role pengguna.
 *
 *   Route::middleware(['auth', 'role:kasir'])->prefix('kasir')->group(...);
 *   Route::middleware(['auth', 'role:waitress,kasir'])->group(...);   // salah satu dari role ini
 *
 * Akun berstatus `inactive` dikeluarkan dari sesi walau masih login.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi admin.']);
        }

        if ($roles !== [] && ! $user->hasRole(...$roles)) {
            abort(403);
        }

        return $next($request);
    }
}
