<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($user = $request->user()) {
            return redirect()->route($user->homeRoute());
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Email di database unik tanpa membedakan huruf besar/kecil (uq_users_email_lower),
        // jadi pencarian saat login juga tidak membedakannya.
        $user = User::whereRaw('lower(email) = ?', [mb_strtolower($data['email'])])->first();

        // password_verify langsung: hash di SQL berawalan $2b$ yang ditolak BcryptHasher
        // bawaan Laravel saat verifikasi algoritma aktif.
        $valid = $user && password_verify($data['password'], $user->password);

        if (! $valid) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau kata sandi salah.']);
        }

        if ($user->status !== 'active') {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Akun ini dinonaktifkan. Hubungi admin.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route($user->homeRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
