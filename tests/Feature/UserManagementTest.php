<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use DatabaseTransactions;

    private function user(string $role, string $status = 'active'): User
    {
        return User::factory()->role($role)->create(['status' => $status]);
    }

    public function test_hanya_admin_boleh_membuka_kelola_pengguna(): void
    {
        $this->actingAs($this->user('kasir'))->get('/admin/users')->assertForbidden();
        $this->actingAs($this->user('admin'))->get('/admin/users')->assertOk()->assertSee('Kelola Pengguna');
    }

    public function test_admin_membuat_akun_baru_yang_bisa_login(): void
    {
        $this->actingAs($this->user('admin'))->post('/admin/users', [
            'name' => 'Pelayan Baru', 'email' => 'baru@test.local', 'role' => 'waitress', 'status' => 'active', 'password' => 'rahasia123',
        ])->assertSessionHasNoErrors();

        $u = User::where('email', 'baru@test.local')->first();
        $this->assertSame('waitress', $u->role);
        $this->assertTrue(password_verify('rahasia123', $u->password));

        auth()->logout();
        $this->post('/login', ['email' => 'baru@test.local', 'password' => 'rahasia123'])->assertRedirect(route('waitress.board'));
    }

    public function test_akun_nonaktif_tidak_bisa_login(): void
    {
        $u = $this->user('kasir', 'inactive');
        $this->post('/login', ['email' => $u->email, 'password' => 'kaleng123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_ubah_role_status_dan_password_opsional(): void
    {
        $admin = $this->user('admin');
        $target = $this->user('kasir');
        $hashLama = $target->password;

        $this->actingAs($admin)->put("/admin/users/{$target->id}", [
            'name' => 'Nama Baru', 'email' => $target->email, 'role' => 'dapur', 'status' => 'inactive', 'password' => '',
        ])->assertSessionHasNoErrors();

        $target->refresh();
        $this->assertSame(['Nama Baru', 'dapur', 'inactive', $hashLama], [$target->name, $target->role, $target->status, $target->password]);
    }

    public function test_admin_tidak_bisa_menurunkan_atau_menonaktifkan_dirinya_sendiri(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'kasir', 'status' => 'active',
        ])->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_email_harus_unik_dan_role_harus_valid(): void
    {
        $admin = $this->user('admin');
        $lain = $this->user('kasir');

        $this->actingAs($admin)->post('/admin/users', ['name' => 'X', 'email' => $lain->email, 'role' => 'kasir', 'status' => 'active', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('email');
        $this->post('/admin/users', ['name' => 'X', 'email' => 'x@test.local', 'role' => 'superadmin', 'status' => 'active', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('role');
    }
}
