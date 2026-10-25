<div class="k3-sidebar-user"
    x-data="{ media: null, onChange: null, init() { this.media = window.matchMedia('(max-width: 1023px)'); this.onChange = () => { if (this.media.matches) this.$store.sidebar.close() }; this.$nextTick(this.onChange); this.media.addEventListener('change', this.onChange) }, destroy() { this.media.removeEventListener('change', this.onChange) } }">
    <span class="k3-user-name">{{ auth()->user()->name }}</span>
    <span class="k3-user-role">{{ \App\Models\User::LABEL_ROLE[auth()->user()->role] }}</span>
    <button type="button" class="k3-close-menu" x-on:click="$store.sidebar.close()">Tutup menu ×</button>
</div>
