<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi rute berdasarkan kolom users.role (admin | kasir | dapur | owner).
 * Pemakaian: ->middleware('role:kasir') atau 'role:admin,owner'.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Akun Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}
