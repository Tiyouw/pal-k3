/* Data dummy bersama 3 sketsa. Kode, lokasi, koordinat = CSV seed asli
   (database/seed/pal_seed_apar_35.csv). Status bulan ini = karangan deterministik. */
const RAW = [["01D","Dry Powder","4,5","Gedung PIP","Lt. 1","Lorong Timur titik 1",-8.186185,113.706195,75],["02D","Dry Powder","4,5","Gedung PIP","Lt. 1","Lorong Timur titik 2",-8.186185,113.706304,75],["03D","Dry Powder","4,5","Gedung PIP","Lt. 1","Lorong Timur titik 3",-8.186185,113.706413,75],["04D","Dry Powder","4,5","Gedung PIP","Lt. 1","Lorong Tengah titik 4",-8.186275,113.706195,75],["05D","Dry Powder","4,5","Gedung PIP","Lt. 1","Lorong Tengah titik 5",-8.186275,113.706304,75],["06D","Dry Powder","4,5","Gedung PIP","Lt. 1","Lorong Tengah titik 6",-8.186275,113.706413,75],["07D","Dry Powder","4,5","Gedung PIP","Lt. 1","Lorong Barat titik 7",-8.186365,113.706249,75],["08D","Dry Powder","4,5","Gedung PIP","Lt. 1","Lorong Barat titik 8",-8.186365,113.706358,75],["09D","Dry Powder","4,5","Gedung PIP","Lt. 2","Lorong Timur titik 1",-8.186185,113.706195,75],["10D","Dry Powder","4,5","Gedung PIP","Lt. 2","Lorong Timur titik 2",-8.186185,113.706304,75],["11D","Dry Powder","4,5","Gedung PIP","Lt. 2","Lorong Timur titik 3",-8.186185,113.706413,75],["12D","Dry Powder","4,5","Gedung PIP","Lt. 2","Lorong Tengah titik 4",-8.186275,113.706195,75],["13D","Dry Powder","4,5","Gedung PIP","Lt. 2","Lorong Tengah titik 5",-8.186275,113.706304,75],["14D","Dry Powder","4,5","Gedung PIP","Lt. 2","Lorong Tengah titik 6",-8.186275,113.706413,75],["15D","Dry Powder","4,5","Gedung PIP","Lt. 2","Lorong Barat titik 7",-8.186365,113.706249,75],["16D","Dry Powder","4,5","Gedung PIP","Lt. 2","Lorong Barat titik 8",-8.186365,113.706358,75],["17D","Dry Powder","4,5","Gedung PIP","Lt. 3","Lorong Timur titik 1",-8.186185,113.706195,75],["18D","Dry Powder","4,5","Gedung PIP","Lt. 3","Lorong Timur titik 2",-8.186185,113.706304,75],["19D","Dry Powder","4,5","Gedung PIP","Lt. 3","Lorong Timur titik 3",-8.186185,113.706413,75],["20D","Dry Powder","4,5","Gedung PIP","Lt. 3","Lorong Tengah titik 4",-8.186275,113.706195,75],["21D","Dry Powder","4,5","Gedung PIP","Lt. 3","Lorong Tengah titik 5",-8.186275,113.706304,75],["22D","Dry Powder","4,5","Gedung PIP","Lt. 3","Lorong Tengah titik 6",-8.186275,113.706413,75],["23D","Dry Powder","4,5","Gedung PIP","Lt. 3","Lorong Barat titik 7",-8.186365,113.706249,75],["24D","Dry Powder","4,5","Gedung PIP","Lt. 3","Lorong Barat titik 8",-8.186365,113.706358,75],["25D","Dry Powder","4,5","Workshop Fabrikasi","Lt. 1","Bay 1",-8.185871,113.706059,60],["26C","CO2","6,0","Workshop Fabrikasi","Lt. 1","Bay 2",-8.185871,113.706195,60],["27D","Dry Powder","4,5","Workshop Fabrikasi","Lt. 1","Bay 3",-8.185871,113.706331,60],["28C","CO2","6,0","Workshop Fabrikasi","Lt. 1","Bay 4",-8.185754,113.706059,60],["29D","Dry Powder","4,5","Workshop Fabrikasi","Lt. 1","Bay 5",-8.185754,113.706195,60],["30C","CO2","6,0","Workshop Fabrikasi","Lt. 1","Bay 6",-8.185754,113.706331,60],["31D","Dry Powder","9,0","Area Terbuka","-","Pos 1",-8.186526,113.705995,30],["32D","Dry Powder","9,0","Area Terbuka","-","Pos 2",-8.186526,113.706286,30],["33D","Dry Powder","9,0","Area Terbuka","-","Pos 3",-8.186634,113.706141,30],["34D","Dry Powder","9,0","Area Terbuka","-","Pos 4",-8.186077,113.705923,30],["35D","Dry Powder","9,0","Area Terbuka","-","Pos 5",-8.186005,113.706485,30]];

const BELUM   = ["08D","15D","16D","19D","20D","23D","24D","27D","29D","31D","33D","34D","35D"];
const TIDAK   = {"14D":"Tekanan manometer di zona merah","06D":"Segel dan mekanik penembus putus","26C":"Pipa atau selang pancar retak","32D":"Badan tabung berkarat berat"};
const CATATAN = {"03D":"Label dan petunjuk pakai pudar","11D":"Tanda pemasangan dan kartu kontrol hilang","18D":"Badan tabung berkarat ringan","25D":"Keterjangkauan terhalang palet"};
const GERBANG = {"07D":["perlu_review","GPS 140 m dari titik, radius 75 m, akurasi ±38 m"],"12D":["gps_lemah","Akurasi GPS ±92 m, sinyal lemah di dalam gedung"],"30C":["tanpa_gps","Izin lokasi ditolak di ponsel petugas"]};
const BERAT = ["14D","32D"];        // temuan berat terbuka
const LEWAT = ["06D"];              // temuan lewat tenggat

const UNITS = RAW.map(([kode,tipe,kg,gedung,lantai,lokasi,lat,lng,radius],i) => {
  const belum = BELUM.includes(kode);
  const kes = belum ? null : TIDAK[kode] ? "tidak_layak" : CATATAN[kode] ? "layak_catatan" : "layak";
  return {
    kode, tipe, kg, gedung, lantai, lokasi, lat, lng, radius,
    area: gedung === "Gedung PIP" ? `Gedung PIP ${lantai}` : gedung,
    belum, kes,
    masalah: TIDAK[kode] || CATATAN[kode] || null,
    gerbang: belum ? null : (GERBANG[kode]?.[0] || "lolos"),
    gerbangKet: GERBANG[kode]?.[1] || null,
    petugas: belum ? null : `Inspektur ${(i % 5) + 1}`,
    waktu: belum ? null : `${String(1 + (i * 7) % 4).padStart(2,"0")} Okt, ${String(8 + (i * 3) % 8).padStart(2,"0")}.${String((i * 17) % 60).padStart(2,"0")}`,
    jarak: belum ? null : (GERBANG[kode] ? null : 4 + (i * 11) % 30),
  };
});

const LABEL_KES = {layak:"Layak pakai", layak_catatan:"Layak dengan catatan", tidak_layak:"Tidak layak"};
const LABEL_GERBANG = {lolos:"Lolos", perlu_review:"Perlu tinjauan", gps_lemah:"GPS lemah", tanpa_gps:"Tanpa GPS"};
const AREA = ["Gedung PIP Lt. 1","Gedung PIP Lt. 2","Gedung PIP Lt. 3","Workshop Fabrikasi","Area Terbuka"];
const perArea = a => { const u = UNITS.filter(x => x.area === a); return {total:u.length, sudah:u.filter(x=>!x.belum).length}; };
const TREN = [["Mei",31],["Jun",33],["Jul",29],["Agu",34],["Sep",35],["Okt",22]]; // unit diperiksa per bulan
const RINGKAS = {persen:"62,9", sudah:22, total:35, belum:13, berat:2, lewat:1, tinjau:3, tenggat:"30 Okt 2026", sisa:26};
const esc = s => String(s ?? "").replace(/[&<>"]/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;"}[c]));
