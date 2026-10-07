/* Ilustrasi APAR (SVG) bersama sketsa A2 dan D.
   Satu gambar bisa "menceritakan" temuan: jarum manometer di zona merah, segel putus,
   selang retak, atau karat. Jadi kartu temuan tidak cuma placeholder foto. */
function aparSVG({w = 120, jarum = 'hijau', segel = true, selang = true, karat = 0, label = 'DP 4,5 kg', judul = 'Ilustrasi APAR'} = {}) {
  // sudut jarum: hijau = tengah (aman), merah = kiri (tekanan kurang)
  const sudut = {hijau: 0, kuning: -38, merah: -68}[jarum] ?? 0;
  const bercak = [[30, 92, 5], [52, 118, 6], [36, 140, 4], [58, 96, 3.5], [44, 128, 3]].slice(0, karat)
    .map(([x, y, r]) => `<circle cx="${x}" cy="${y}" r="${r}" fill="#7a3b12" opacity=".75"/><circle cx="${x + 1}" cy="${y - 1}" r="${r * .45}" fill="#a65a22" opacity=".8"/>`).join('');
  const h = Math.round(w * 1.6);
  // id gradien unik per gambar: satu halaman bisa memuat banyak APAR
  const n = (aparSVG.n = (aparSVG.n || 0) + 1);
  return `<svg width="${w}" height="${h}" viewBox="0 0 100 160" role="img" aria-label="${judul}">
  <defs>
    <linearGradient id="tb${n}" x1="0" x2="1"><stop offset="0" stop-color="#9e1622"/><stop offset=".35" stop-color="#e2323f"/><stop offset=".6" stop-color="#d2232f"/><stop offset="1" stop-color="#8a111c"/></linearGradient>
    <linearGradient id="kp${n}" x1="0" x2="1"><stop offset="0" stop-color="#3a3f45"/><stop offset=".5" stop-color="#6b7178"/><stop offset="1" stop-color="#2b2f34"/></linearGradient>
  </defs>
  <ellipse cx="46" cy="156" rx="30" ry="3.5" fill="#000" opacity=".18"/>
  <!-- selang -->
  <path d="M60 30 C84 34 88 60 80 92 C76 108 72 118 70 128" fill="none" stroke="#1d2125" stroke-width="5" stroke-linecap="round"/>
  ${selang ? '' : '<path d="M77 98 l6 -4 M75 104 l7 1" stroke="#f5b700" stroke-width="2.4" stroke-linecap="round"/><circle cx="79" cy="100" r="7" fill="none" stroke="#f5b700" stroke-width="1.6" stroke-dasharray="2 2"/>'}
  <rect x="64" y="126" width="11" height="12" rx="2" fill="#1d2125"/>
  <!-- tabung -->
  <rect x="18" y="44" width="56" height="108" rx="20" fill="url(#tb${n})"/>
  <rect x="25" y="52" width="6" height="90" rx="3" fill="#fff" opacity=".22"/>
  ${bercak}
  <!-- label -->
  <rect x="26" y="98" width="40" height="30" rx="4" fill="#fff" opacity=".93"/>
  <text x="46" y="110" text-anchor="middle" font-size="7.2" font-weight="800" fill="#14181b" font-family="inherit">APAR</text>
  <text x="46" y="121" text-anchor="middle" font-size="6" font-weight="600" fill="#5d6670" font-family="inherit">${label}</text>
  <!-- leher & kepala -->
  <rect x="36" y="34" width="20" height="13" rx="3" fill="url(#kp${n})"/>
  <path d="M30 26 h36 l-6 9 H34z" fill="url(#kp${n})"/>
  <path d="M33 26 C30 12 48 8 66 14 l-2 5 C50 15 40 17 41 26z" fill="#2b2f34"/>
  <!-- segel & pin -->
  <circle cx="54" cy="22" r="3.5" fill="none" stroke="#f5b700" stroke-width="2"/>
  ${segel ? '<path d="M56 24 q6 6 2 12" fill="none" stroke="#2fa36b" stroke-width="1.6"/>' : '<path d="M56 24 q4 3 3 6 M58 33 q2 2 0 4" fill="none" stroke="#ff6b78" stroke-width="1.6"/><text x="64" y="34" font-size="9" font-weight="800" fill="#ff6b78" font-family="inherit">!</text>'}
  <!-- manometer -->
  <circle cx="24" cy="40" r="11" fill="#f4f5f7" stroke="#2b2f34" stroke-width="2.4"/>
  <path d="M15.5 44 A9 9 0 0 1 18 32.6" fill="none" stroke="#e2323f" stroke-width="2.6"/>
  <path d="M18 32.6 A9 9 0 0 1 30 32.6" fill="none" stroke="#2fa36b" stroke-width="2.6"/>
  <path d="M30 32.6 A9 9 0 0 1 32.5 44" fill="none" stroke="#f5b700" stroke-width="2.6"/>
  <g transform="rotate(${sudut} 24 40)"><path d="M24 40 L24 31.5" stroke="#14181b" stroke-width="1.8" stroke-linecap="round"/></g>
  <circle cx="24" cy="40" r="1.8" fill="#14181b"/>
</svg>`;
}
// pemetaan masalah seed ke keadaan ilustrasi
function aparUntuk(u) {
  const m = (u && u.masalah) || '';
  return {
    jarum: /manometer/i.test(m) ? 'merah' : 'hijau',
    segel: !/segel/i.test(m),
    selang: !/selang|pipa/i.test(m),
    karat: /karat berat/i.test(m) ? 5 : /karat/i.test(m) ? 2 : 0,
    label: u ? `${u.tipe === 'CO2' ? 'CO₂' : 'DP'} ${u.kg} kg` : 'DP 4,5 kg',
    judul: u ? `Ilustrasi ${u.kode}: ${m || 'kondisi baik'}` : 'Ilustrasi APAR',
  };
}
