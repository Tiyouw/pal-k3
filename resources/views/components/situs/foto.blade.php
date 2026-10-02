{{--
    Foto media PT PAL dengan srcset. Berkas ada di public/media/pal dengan pola
    <berkas>-<lebar>.webp (asal-usulnya di public/media/pal/SUMBER.md).
    Ukuran tampil diatur pembungkusnya; foto selalu menutup bidang.

    <x-situs.foto berkas="bengkel-pengelasan" :lebar="[800, 1600]"
                  sizes="(min-width: 48rem) 33vw, 100vw" alt="..." />
--}}
@props([
    'berkas',
    'alt',
    'lebar' => [800, 1600],
    'sizes' => '100vw',
])

@php
    $daftar = collect($lebar)->sort()->values();
    $url = fn (int $w): string => asset("media/pal/{$berkas}-{$w}.webp");
@endphp

<img
    src="{{ $url($daftar->last()) }}"
    srcset="{{ $daftar->map(fn (int $w) => $url($w).' '.$w.'w')->implode(', ') }}"
    sizes="{{ $sizes }}"
    alt="{{ $alt }}"
    loading="lazy"
    decoding="async"
    {{ $attributes->class('size-full object-cover') }}
>
