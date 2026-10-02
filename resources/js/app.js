/**
 * Perilaku halaman publik (layouts/situs): nav yang berubah saat digulir, menu
 * ponsel, reveal saat gulir, dan video latar hero.
 *
 * JavaScript vanilla tanpa pustaka animasi. Semua gerak tunduk pada
 * prefers-reduced-motion; gaya reveal sendiri ada di resources/css/app.css.
 */

const kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)');
const adaPengamat = 'IntersectionObserver' in window;

/** Nav transparan selama hero masih di bawahnya; setelah itu berlatar kertas. */
function pasangNav() {
    const nav = document.querySelector('[data-nav]');
    if (!nav) return;

    const hero = document.querySelector('[data-hero]');
    const setel = (tergulir) => nav.toggleAttribute('data-tergulir', tergulir);

    if (!hero) {
        setel(true);
        return;
    }

    if (adaPengamat) {
        new IntersectionObserver(([entri]) => setel(!entri.isIntersecting), {
            rootMargin: `-${nav.offsetHeight}px 0px 0px 0px`,
        }).observe(hero);
        return;
    }

    const periksa = () => setel(hero.getBoundingClientRect().bottom <= nav.offsetHeight);
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
 */
function pasangVideoLatar() {
    const video = document.querySelector('[data-video-latar]');
    if (!video) return;

    const koneksi = navigator.connection;
    if (koneksi && (koneksi.saveData || /2g$/.test(koneksi.effectiveType ?? ''))) return;

    let siap = document.readyState === 'complete';
    let terlihat = true;

    const perbarui = () => {
        if (!siap) return;

        if (kurangiGerak.matches) {
            // Kembali ke poster, bukan sekadar berhenti di bingkai acak.
            if (video.getAttribute('src')) {
                video.pause();
                video.removeAttribute('src');
                video.load();
            }
            return;
        }

        if (!terlihat) {
            video.pause();
            return;
        }

        if (!video.getAttribute('src')) {
            const tegak = window.matchMedia('(orientation: portrait)').matches;
            video.src = tegak ? video.dataset.srcTegak : video.dataset.srcLebar;
        }
        video.play()?.catch(() => {});
    };

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

pasangNav();
pasangMenu();
pasangMuncul();
pasangVideoLatar();
