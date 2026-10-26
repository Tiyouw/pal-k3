<div class="k3-login-shell">
    <x-admin.cerita-masuk />
    <section class="k3-login-form-panel">
        <a href="{{ route('landing') }}" class="k3-login-back">← Kembali ke beranda</a>
        <div class="k3-login-form-content"><span class="k3-login-eyebrow">PANEL PENGELOLAAN</span><h2>Selamat datang kembali.</h2><p class="k3-login-intro">{{ config('demo.enabled') ? 'Gunakan salah satu akun demo di bawah.' : 'Masuk untuk memantau pemeriksaan dan menindaklanjuti temuan APAR.' }}</p>
            <x-filament-panels::form id="form" wire:submit="authenticate">
                {{ $this->form }}
                <x-filament-panels::form.actions :actions="$this->getCachedFormActions()" :full-width="true" />
            </x-filament-panels::form>
            @unless (config('demo.enabled'))<p class="k3-login-help">Lupa kata sandi? Hubungi Administrator K3 Divisi K3LH.</p>@endunless
            <x-admin.akun-demo :roles="['admin', 'pemantau']" />
            <div class="k3-login-switch">Bertugas di lapangan? <a href="{{ route('petugas.masuk') }}">Masuk sebagai petugas ↗</a></div>
        </div><p class="k3-login-foot">Akses terbatas untuk Administrator K3 dan Pemantau.</p>
    </section>
</div>
