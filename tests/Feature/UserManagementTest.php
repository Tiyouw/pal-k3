<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function panel(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_admin_membuat_petugas_yang_bisa_login(): void
    {
        $this->panel();

        Livewire::test(CreateUser::class)->fillForm([
            'nip' => '2999', 'name' => 'Petugas Uji', 'email' => 'petugas.uji@example.test',
            'role' => 'inspektur', 'password' => 'petugas1234', 'aktif' => true,
        ])->call('create')->assertHasNoFormErrors();

        $user = User::where('nip', '2999')->firstOrFail();
        $this->assertSame('inspektur', $user->role);
        $this->assertTrue(Hash::check('petugas1234', $user->getAuthPassword()));
        Auth::logout();
        $this->post('/masuk', ['nip' => '2999', 'password' => 'petugas1234'])->assertRedirect('/petugas');
        $this->assertAuthenticatedAs($user);
    }

    public function test_ubah_profil_tanpa_sandi_mempertahankan_sandi_lama_dan_bisa_menonaktifkan_akun(): void
    {
        $this->panel();
        $user = User::factory()->create(['password' => 'petugas1234']);
        $password = $user->getAuthPassword();

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['name' => 'Nama Diperbarui', 'aktif' => false])->call('save')->assertHasNoFormErrors();

        $this->assertSame('Nama Diperbarui', $user->fresh()->name);
        $this->assertSame($password, $user->fresh()->getAuthPassword());
        Auth::logout();
        $this->post('/masuk', ['nip' => $user->nip, 'password' => 'petugas1234'])->assertSessionHasErrors('nip');
        $this->assertGuest();
    }

    public function test_pemantau_tidak_bisa_membuka_pengelolaan_petugas(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->pemantau()->create());

        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/users/create')->assertForbidden();

        $this->assertFalse(UserResource::shouldRegisterNavigation());
        $this->assertFalse(UserResource::canCreate());
    }

    public function test_petugas_dinonaktifkan_sebagai_pengganti_hapus_agar_riwayat_tetap_ada(): void
    {
        $this->panel();
        $user = User::factory()->create();

        $this->assertFalse(UserResource::canDelete($user));
        $this->assertFalse(UserResource::canDeleteAny());

        $this->get('/admin/users')->assertOk()->assertSee($user->nip);
    }
}
