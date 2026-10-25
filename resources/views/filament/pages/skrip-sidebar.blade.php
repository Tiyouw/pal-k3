{{--
    Status sidebar panel.

    Filament menyimpan satu kunci 'isOpen' di localStorage yang dipakai bersama oleh laci menu ponsel dan
    ciut-ke-ikon di desktop. Akibatnya menutup laci di ponsel (atau mengecilkan jendela) ikut menimpa
    pilihan desktop. Di sini pilihan desktop disimpan di kunci sendiri ('k3SidebarDesktop'):
      - desktop: memakai pilihan tersimpan; pengguna baru melihat sidebar ikon dulu;
      - ponsel : laci selalu tertutup saat halaman dibuka.
    Skrip ini jalan di <head>, sebelum Alpine membaca 'isOpen', jadi tidak ada kedipan terbuka-lalu-ciut.
    Ctrl/Cmd+B membuka-menutup sidebar, kecuali saat fokus di editor teks (di sana Ctrl+B = tebal).
--}}
<script>
    (function () {
        var KUNCI = 'k3SidebarDesktop';
        var desktop = window.matchMedia('(min-width: 1024px)');
        var baca = function () { try { return localStorage.getItem(KUNCI); } catch (e) { return null; } };
        var tulis = function (k, v) { try { localStorage.setItem(k, v); } catch (e) {} };

        tulis('isOpen', JSON.stringify(desktop.matches ? baca() === 'terbuka' : false));

        document.addEventListener('alpine:initialized', function () {
            var sidebar = window.Alpine.store('sidebar');
            var sinkron = function () {
                if (desktop.matches) { tulis(KUNCI, sidebar.isOpen ? 'terbuka' : 'ikon'); }
                document.documentElement.classList.toggle('k3-sb-ikon', desktop.matches && !sidebar.isOpen);
            };
            window.Alpine.effect(sinkron);
            desktop.addEventListener('change', function () {
                sidebar.isOpen = desktop.matches ? baca() === 'terbuka' : false;
                sinkron();
            });
        });

        document.addEventListener('keydown', function (e) {
            if (!(e.ctrlKey || e.metaKey) || e.shiftKey || e.altKey || String(e.key).toLowerCase() !== 'b') { return; }
            if (e.target && e.target.closest && e.target.closest('[contenteditable=""],[contenteditable="true"],trix-editor,.ProseMirror')) { return; }
            var sidebar = window.Alpine && window.Alpine.store('sidebar');
            if (!sidebar) { return; }
            e.preventDefault();
            sidebar.isOpen = !sidebar.isOpen;
        });
    })();
</script>
