<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;

/** Tabel `users` (kafe_kaleng.sql): role = admin | kasir | dapur | owner | waitress. */
#[Fillable(['name', 'email', 'password', 'role', 'status'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    public const HOME = [
        'admin' => 'admin.dashboard',
        'kasir' => 'kasir.dashboard',
        'dapur' => 'dapur.board',
        'owner' => 'owner.dashboard',
        'waitress' => 'waitress.board',
    ];

    public const ROLE_LABEL = ['admin' => 'Admin', 'kasir' => 'Kasir', 'dapur' => 'Dapur', 'owner' => 'Owner', 'waitress' => 'Pelayan'];

    public function homeRoute(): string
    {
        return self::HOME[$this->role] ?? 'login';
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABEL[$this->role] ?? ucfirst((string) $this->role);
    }
}
