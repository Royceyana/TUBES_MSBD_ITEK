<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Kelola pengguna (admin): nama, email, role, status, dan kata sandi.
 * Akun tidak dihapus, hanya dinonaktifkan (status = inactive), karena transaksi
 * dan riwayat stok memegang user_id.
 */
class UserController extends Controller
{
    public function index(): View
    {
        $users = User::orderByRaw("array_position(array['admin','owner','kasir','waitress','dapur'], role::text)")
            ->orderBy('name')->get();

        return view('admin.users', compact('users'));
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLE_LABEL))],
            'status' => ['required', 'in:active,inactive'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:100'],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return back()->with('success', "Akun {$data['name']} dibuat.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate($this->rules($user));
        $this->guardAdminAccess($request, $user, $data);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);

        return back()->with('success', "Akun {$user->name} diperbarui.");
    }

    /** Cegah admin mengunci dirinya sendiri atau menghapus akses admin terakhir. */
    private function guardAdminAccess(Request $request, User $user, array $data): void
    {
        $losesAdmin = $user->role === 'admin' && $user->status === 'active'
            && ($data['role'] !== 'admin' || $data['status'] !== 'active');

        if (! $losesAdmin) {
            return;
        }

        if ($request->user()->id === $user->id) {
            throw ValidationException::withMessages(['role' => 'Anda tidak bisa menonaktifkan atau mengubah role akun Anda sendiri.']);
        }

        $others = User::where('role', 'admin')->where('status', 'active')->where('id', '!=', $user->id)->count();
        if ($others === 0) {
            throw ValidationException::withMessages(['role' => 'Harus ada minimal satu admin aktif.']);
        }
    }
}
