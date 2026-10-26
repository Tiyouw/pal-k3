<?php

namespace Tests\Feature;

use App\Filament\Auth\Masuk;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\MasterSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DemoLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['demo.enabled' => true]);
    }

    #[DataProvider('akunDemo')]
    public function test_akun_demo_login_dengan_sandi_sederhana_tanpa_diubah_menjadi_hash(string $username, string $password, string $redirect): void
    {
        $this->seed([MasterSeeder::class, DemoUserSeeder::class]);
        $user = User::where('nip', $username)->firstOrFail();

        $this->post('/masuk', ['nip' => $username, 'password' => $password, 'ingat' => '1'])
            ->assertRedirect($redirect)->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertSame($password, $user->fresh()->getRawOriginal('password'));
    }

    public static function akunDemo(): array
    {
        return [
            'admin' => ['admin', 'admin123', '/admin'],
            'pemantau' => ['pemantau', 'pemantau123', '/admin'],
            'inspektur' => ['inspektur', 'inspektur123', '/petugas'],
        ];
    }

    #[DataProvider('akunPanel')]
    public function test_akun_demo_bisa_masuk_melalui_form_panel(string $username, string $password): void
    {
        $this->seed([MasterSeeder::class, DemoUserSeeder::class]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Masuk::class)->fillForm(['nip' => $username, 'password' => $password])
            ->call('authenticate')->assertHasNoFormErrors()->assertRedirect('/admin');

        $this->assertAuthenticatedAs(User::where('nip', $username)->firstOrFail());
    }

    public static function akunPanel(): array
    {
        return ['admin' => ['admin', 'admin123'], 'pemantau' => ['pemantau', 'pemantau123']];
    }

    public function test_sandi_salah_dan_akun_nonaktif_ditolak(): void
    {
        $this->seed([MasterSeeder::class, DemoUserSeeder::class]);

        $this->post('/masuk', ['nip' => 'admin', 'password' => 'salah123'])->assertSessionHasErrors('nip');
        User::where('nip', 'admin')->update(['aktif' => false]);
        $this->post('/masuk', ['nip' => 'admin', 'password' => 'admin123'])->assertSessionHasErrors('nip');

        $this->assertGuest();
    }

    public function test_mematikan_demo_menolak_sandi_teks_biasa_dan_menyembunyikan_petunjuk(): void
    {
        $this->seed([MasterSeeder::class, DemoUserSeeder::class]);
        config(['demo.enabled' => false]);

        $this->post('/masuk', ['nip' => 'admin', 'password' => 'admin123'])->assertSessionHasErrors('nip');
        $this->get('/masuk')->assertOk()->assertDontSee('inspektur123');
        $this->get('/admin/login')->assertOk()->assertDontSee('admin123');

        $this->assertGuest();
    }

    public function test_akun_nip_tetap_bisa_login_dengan_hash_saat_demo_aktif(): void
    {
        $user = User::factory()->create(['nip' => '2009', 'password' => 'petugas1234']);

        $this->post('/masuk', ['nip' => '2009', 'password' => 'petugas1234'])->assertRedirect('/petugas');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('petugas1234', $user->fresh()->getAuthPassword()));
    }

    public function test_seeder_demo_idempoten_dan_tidak_mengganti_akun_nip(): void
    {
        $user = User::factory()->admin()->create(['nip' => '1001']);
        $password = $user->getAuthPassword();
        $this->seed([MasterSeeder::class, DemoUserSeeder::class]);

        $this->seed(DemoUserSeeder::class);

        $this->assertDatabaseCount('users', 4);
        $this->assertSame($password, $user->fresh()->getAuthPassword());
    }

    public function test_seeder_demo_tidak_membuat_akun_saat_nonaktif(): void
    {
        config(['demo.enabled' => false]);

        $this->seed(DemoUserSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }
}
