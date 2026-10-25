{{-- Merek Geofencing untuk panel dan halaman masuk. Path tanda sama dengan components/situs/logo.blade.php
     dan public/geofencing.svg (halaman depan); di sini dibuat tanpa kelas Tailwind v4 karena tema Filament
     tidak memuatnya. Warna mengikuti currentColor; pin diberi aksen lewat --k3-pin di latar gelap. --}}
<span class="k3-merek">
    <svg class="k3-merek-tanda" viewBox="0 0 36 36" fill="none" aria-hidden="true" focusable="false">
        <path class="k3-merek-sudut" d="M11 3H3v8M25 3h8v8M33 25v8h-8M11 33H3v-8" />
        <g class="k3-merek-pin">
            <path d="M26 15c0 6-8 13-8 13s-8-7-8-13a8 8 0 1 1 16 0Z" />
            <circle cx="18" cy="15" r="2.75" />
        </g>
    </svg>
    <span class="k3-merek-teks"><b>Geofencing</b><small>Inspeksi K3 APAR</small></span>
</span>
