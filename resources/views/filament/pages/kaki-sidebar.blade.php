<div class="k3-sidebar-footer"
    x-data="{ media: null, onChange: null, init() { this.media = window.matchMedia('(max-width: 1023px)'); this.onChange = () => { if (this.media.matches) this.$store.sidebar.close() }; this.$nextTick(this.onChange); this.media.addEventListener('change', this.onChange) }, destroy() { this.media.removeEventListener('change', this.onChange) } }">
    <button type="button" class="k3-close-menu" x-on:click="$store.sidebar.close()">Tutup menu ×</button>
    <p>PERIODE PEMERIKSAAN</p>
    <strong>{{ now()->translatedFormat('F Y') }}</strong>
    <span>Pantau pemeriksaan dan tindak lanjut dalam satu tempat.</span>
    <a href="{{ route('laporan.index') }}">Buka laporan bulanan <span aria-hidden="true">↗</span></a>
    <small>{{ \App\Models\User::LABEL_ROLE[auth()->user()->role] }}</small>
</div>
