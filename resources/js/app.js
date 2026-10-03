/**
 * Perilaku halaman publik (layouts/situs): nav yang berubah saat digulir, menu
 * ponsel, reveal saat gulir, dan video latar hero beserta tombol jedanya.
 *
 * JavaScript vanilla tanpa pustaka animasi. Semua gerak tunduk pada
 * prefers-reduced-motion; gaya reveal sendiri ada di resources/css/app.css.
 */

const kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)');
const adaPengamat = 'IntersectionObserver' in window;

/**
 * Nav transparan hanya saat halaman diam di puncak hero; begitu digulir
 * sedikit, nav berlatar kertas.
 *
 * Versi awal menunggu seluruh hero (setinggi layar) lewat di bawah nav. Karena
 * nav fixed, selama itu judul, paragraf, dan tombol jeda hero bergulir persis
 * di bawah logo dan tombol nav yang masih transparan, jadi teksnya bertabrakan.
 * Sekarang yang diamati penanda [data-nav-penanda] setinggi 8px di puncak hero:
 * begitu keluar layar, nav menjadi solid.
 */
function pasangNav() {
    const nav = document.querySelector('[data-nav]');
    if (!nav) return;

    const penanda = document.querySelector('[data-nav-penanda]');
    const setel = (tergulir) => nav.toggleAttribute('data-tergulir', tergulir);

    if (!penanda) {
        setel(true);
        return;
    }

    if (adaPengamat) {
        new IntersectionObserver(([entri]) => setel(!entri.isIntersecting)).observe(penanda);
        return;
    }

    const periksa = () => setel(penanda.getBoundingClientRect().bottom <= 0);
    periksa();
    window.addEventListener('scroll', periksa, { passive: true });
}

/** Menu ponsel: tombol dua garis membuka panel tautan sederhana. */
function pasangMenu() {
    const tombol = document.querySelector('[data-menu-tombol]');
    const panel = tombol && document.getElementById(tombol.getAttribute('aria-controls'));
    if (!panel) return;

    const nav = tombol.closest('[data-nav]');
    const terbuka = () => tombol.getAttribute('aria-expanded') === 'true';
    const atur = (buka) => {
        tombol.setAttribute('aria-expanded', String(buka));
        tombol.setAttribute('aria-label', buka ? 'Tutup menu' : 'Buka menu');
        panel.hidden = !buka;
        nav?.toggleAttribute('data-menu-terbuka', buka);
    };

    tombol.addEventListener('click', () => atur(!terbuka()));
    panel.addEventListener('click', (e) => {
        if (e.target.closest('a')) atur(false);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape' || !terbuka()) return;
        atur(false);
        tombol.focus();
    });
    window.matchMedia('(min-width: 64rem)').addEventListener('change', (e) => {
        if (e.matches) atur(false);
    });
}

/** Reveal saat gulir: elemen [data-muncul] mendapat .muncul begitu masuk layar. */
function pasangMuncul() {
    const elemen = document.querySelectorAll('[data-muncul]');
    if (!elemen.length) return;

    if (kurangiGerak.matches || !adaPengamat) {
        elemen.forEach((el) => el.classList.add('muncul'));
        return;
    }

    const pengamat = new IntersectionObserver(
        (daftar) => {
            for (const entri of daftar) {
                if (!entri.isIntersecting) continue;
                entri.target.classList.add('muncul');
                pengamat.unobserve(entri.target);
            }
        },
        { rootMargin: '0px 0px -8% 0px' },
    );

    elemen.forEach((el) => pengamat.observe(el));
}

/**
 * Video latar hero. Sumbernya baru dipasang setelah halaman selesai dimuat, dan
 * tidak dipasang sama sekali bila pengguna meminta gerak dikurangi atau sedang
 * menghemat data; poster tetap tampil. Layar potret memakai potongan tegak yang
 * jauh lebih kecil. Video dijeda saat hero keluar layar.
 *
 * Tombol [data-video-kendali] (WCAG 2.2.2) baru ditampilkan setelah video
 * benar-benar berputar, jadi tetap tersembunyi selama video tidak dimuat. Video
 * yang dijeda pengguna tidak diputar ulang otomatis saat hero kembali terlihat.
 */
function pasangVideoLatar() {
    const video = document.querySelector('[data-video-latar]');
    if (!video) return;

    const koneksi = navigator.connection;
    if (koneksi && (koneksi.saveData || /2g$/.test(koneksi.effectiveType ?? ''))) return;

    const tombol = document.querySelector('[data-video-kendali]');
    let siap = document.readyState === 'complete';
    let terlihat = true;
    let dijedaPengguna = false;

    const tulisTombol = () => {
        if (tombol) tombol.textContent = dijedaPengguna ? 'Putar video' : 'Jeda video';
    };

    const perbarui = () => {
        if (!siap) return;

        if (kurangiGerak.matches) {
            // Kembali ke poster, bukan sekadar berhenti di bingkai acak.
            if (video.getAttribute('src')) {
                video.pause();
                video.removeAttribute('src');
                video.load();
            }
            if (tombol) tombol.hidden = true;
            dijedaPengguna = false;
            tulisTombol();
            return;
        }

        if (!terlihat || dijedaPengguna) {
            video.pause();
            return;
        }

        if (!video.getAttribute('src')) {
            const tegak = window.matchMedia('(orientation: portrait)').matches;
            video.src = tegak ? video.dataset.srcTegak : video.dataset.srcLebar;
        }
        video.play()?.catch(() => {});
    };

    if (tombol) {
        video.addEventListener('playing', () => {
            tombol.hidden = false;
        });
        tombol.addEventListener('click', () => {
            dijedaPengguna = !dijedaPengguna;
            tulisTombol();
            perbarui();
        });
    }

    if (!siap) {
        window.addEventListener(
            'load',
            () => {
                siap = true;
                perbarui();
            },
            { once: true },
        );
    }

    kurangiGerak.addEventListener('change', perbarui);

    if (adaPengamat) {
        new IntersectionObserver(([entri]) => {
            terlihat = entri.isIntersecting;
            perbarui();
        }).observe(video);
    }

    perbarui();
}

/*
 * Tiap pemasang diisolasi: galatnya dicatat ke konsol tanpa menghentikan yang
 * lain. pasangMuncul paling awal karena hanya dia yang membuka isi yang
 * disembunyikan kelas js. Tanda siap hanya dipasang bila semua yang ditopang
 * kelas js (reveal, nav, menu) terpasang; bila ada yang gagal, kelas js
 * langsung dicabut dan halaman kembali ke tata letak tanpa JavaScript. Bila
 * modul ini tidak jalan sama sekali, skrip sebaris di layouts/situs yang
 * mencabutnya setelah ±3 detik.
 */
let utuh = true;

function jalankan(nama, pasang, ditopangKelasJs) {
    try {
        pasang();
    } catch (galat) {
        console.error(`[situs] ${nama} gagal:`, galat);
        if (ditopangKelasJs) utuh = false;
    }
}

jalankan('pasangMuncul', pasangMuncul, true);
jalankan('pasangNav', pasangNav, true);
jalankan('pasangMenu', pasangMenu, true);
jalankan('pasangVideoLatar', pasangVideoLatar, false);

if (utuh) document.documentElement.dataset.situsSiap = '1';
else document.documentElement.classList.remove('js');
