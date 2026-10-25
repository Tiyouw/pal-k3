@php
    $pengguna = auth()->user();
    $peran = \App\Models\User::LABEL_ROLE[$pengguna->role] ?? $pengguna->role;
    $inisial = \Illuminate\Support\Str::of($pengguna->name)->explode(' ')->filter()->take(2)
        ->map(fn ($kata) => \Illuminate\Support\Str::substr($kata, 0, 1))->implode('');
@endphp
{{--
    Kaki sidebar: tombol ciutkan dan identitas pengguna selalu menempel di dasar sidebar (sidebar setinggi layar,
    daftar menu yang menggulir di dalamnya), jadi keduanya tidak perlu dicari dengan menggulir.
    Saat ciut di desktop: tombol tinggal ikon dan identitas tinggal inisial, keduanya dengan tooltip.
--}}
<div class="k3-kaki" x-data="{ mac: /Mac|iPhone|iPad/.test(navigator.platform) }">
    <button
        type="button"
        class="k3-ciut"
        x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
        x-bind:aria-label="$store.sidebar.isOpen ? 'Ciutkan menu' : 'Buka menu'"
        aria-keyshortcuts="Control+B Meta+B"
        x-data="{ tooltip: false }"
        x-effect="tooltip = $store.sidebar.isOpen ? false : { content: 'Buka menu', placement: 'right', theme: $store.theme }"
        x-tooltip="tooltip"
    >
        <svg class="k3-ciut-ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <rect x="3" y="4" width="18" height="16" rx="2.5" />
            <path d="M9 4v16" />
            <path class="k3-ciut-panah" d="M15.5 9.5 13 12l2.5 2.5" />
        </svg>
        <span class="k3-ciut-teks" x-show="$store.sidebar.isOpen">Ciutkan menu</span>
        <kbd class="k3-kbd" x-show="$store.sidebar.isOpen" x-text="mac ? '⌘ B' : 'Ctrl B'"></kbd>
    </button>

    <div
        class="k3-sidebar-user"
        x-data="{ tooltip: false }"
        x-effect="tooltip = $store.sidebar.isOpen ? false : { content: @js($pengguna->name.' · '.$peran), placement: 'right', theme: $store.theme }"
        x-tooltip="tooltip"
    >
        <span class="k3-user-ini" aria-hidden="true">{{ $inisial }}</span>
        <span class="k3-user-teks" x-show="$store.sidebar.isOpen">
            <span class="k3-user-name">{{ $pengguna->name }}</span>
            <span class="k3-user-role">{{ $peran }}</span>
        </span>
    </div>

    <button type="button" class="k3-close-menu" x-on:click="$store.sidebar.close()">Tutup menu ×</button>
</div>
