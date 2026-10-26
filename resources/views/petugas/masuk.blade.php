<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#031e25"><meta name="robots" content="noindex, nofollow">
    <title>Masuk Petugas — Inspeksi K3 APAR</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('geofencing.svg') }}">
    @vite('resources/css/masuk.css')
</head>
<body class="k3-login-body">
<main class="k3-login-main">
    <div class="k3-login-shell">
        <x-admin.cerita-masuk />
        <section class="k3-login-form-panel" aria-labelledby="judul-masuk">
            <a href="{{ route('landing') }}" class="k3-login-back">← Kembali ke beranda</a>
            <div class="k3-login-form-content"><span class="k3-login-eyebrow">PORTAL PETUGAS</span><h2 id="judul-masuk">Siap menjaga keselamatan?</h2><p class="k3-login-intro">{{ config('demo.enabled') ? 'Gunakan akun demo untuk mencoba pemeriksaan.' : 'Masuk dengan NIP untuk memulai pemeriksaan dan mencatat kondisi APAR di lapangan.' }}</p>
                @if(session('pesan'))<div class="k3-login-notice" role="status">{{ session('pesan') }}</div>@endif
                <form method="POST" action="{{ route('petugas.masuk.kirim') }}" novalidate>
                    @csrf
                    <div class="k3-login-field"><label for="nip">{{ config('demo.enabled') ? 'NIP atau username' : 'NIP' }}</label><input type="text" id="nip" name="nip" value="{{ old('nip') }}" autocomplete="username" autocapitalize="off" autocorrect="off" spellcheck="false" required maxlength="32" placeholder="{{ config('demo.enabled') ? 'Contoh: inspektur' : 'Masukkan NIP Anda' }}" @if($errors->has('nip')) aria-invalid="true" aria-describedby="galat-nip" @endif>
                        @error('nip')<p class="k3-login-error" id="galat-nip" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div class="k3-login-field"><label for="password">Kata sandi</label><div class="k3-password-wrap"><input type="password" id="password" name="password" autocomplete="current-password" required placeholder="Masukkan kata sandi" @if($errors->has('password')) aria-invalid="true" aria-describedby="galat-password" @endif><button type="button" class="k3-password-toggle" id="lihat" aria-label="Tampilkan kata sandi" aria-pressed="false">Lihat</button></div>
                        @error('password')<p class="k3-login-error" id="galat-password" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <label class="k3-login-remember"><input type="checkbox" name="ingat" value="1" @checked(old('ingat'))>Ingat perangkat ini</label>
                    <button type="submit" class="k3-login-submit">Masuk <span aria-hidden="true">→</span></button>
                </form>
                @unless (config('demo.enabled'))<p class="k3-login-help">Lupa kata sandi? Hubungi Administrator K3 Divisi K3LH.</p>@endunless
                <x-admin.akun-demo :roles="['inspektur']" />
                <div class="k3-login-switch">Administrator atau Pemantau? <a href="{{ route('filament.admin.auth.login') }}">Masuk ke panel pengelolaan ↗</a></div>
            </div><p class="k3-login-foot">Gunakan akun Anda sendiri untuk setiap pemeriksaan.</p>
        </section>
    </div>
</main>
<script>
    document.getElementById('lihat').addEventListener('click', function () {
        const input = document.getElementById('password');
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        this.setAttribute('aria-pressed', String(visible));
        this.setAttribute('aria-label', visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        this.textContent = visible ? 'Tutup' : 'Lihat';
    });
</script>
</body>
</html>
