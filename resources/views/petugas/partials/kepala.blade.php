@auth
    <header class="kepala">
        @hasSection('kembali')
            @yield('kembali')
        @else
            <a href="{{ route('petugas.beranda') }}" aria-label="Kembali ke beranda">&#8962;</a>
        @endif

        <div style="flex:1; min-width:0">
            <h1 style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">
                @yield('judul-kepala', 'Inspeksi K3')
            </h1>
            <div class="sub">@yield('sub-kepala', auth()->user()->name)</div>
        </div>

        <form method="POST" action="{{ route('petugas.keluar') }}">
            @csrf
            <button type="submit" aria-label="Keluar dari akun" title="Keluar">&#9099;</button>
        </form>
    </header>
@endauth
