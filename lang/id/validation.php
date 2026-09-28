<?php

/**
 * Pesan validasi Bahasa Indonesia.
 *
 * Alasan berkas ini ada: APP_LOCALE sudah "id" tetapi direktori lang/ belum
 * pernah dibuat, sehingga Laravel jatuh ke pesan Inggris bawaannya. Akibatnya
 * petugas di lapangan melihat "The NIP field is required." pada halaman masuk.
 *
 * Yang diterjemahkan dibatasi pada aturan yang benar-benar dipakai aplikasi ini
 * (masuk dengan NIP, unggah foto temuan, saringan laporan, form panel), bukan
 * seluruh daftar aturan Laravel, supaya berkas tetap bisa dibaca dan dirawat.
 */
return [
    'accepted' => ':attribute wajib disetujui.',
    'after' => ':attribute harus tanggal setelah :date.',
    'after_or_equal' => ':attribute harus tanggal sama dengan atau setelah :date.',
    'array' => ':attribute harus berupa daftar.',
    'before' => ':attribute harus tanggal sebelum :date.',
    'before_or_equal' => ':attribute harus tanggal sama dengan atau sebelum :date.',
    'between' => [
        'array' => ':attribute harus berisi antara :min sampai :max item.',
        'file' => 'Ukuran :attribute harus antara :min sampai :max kilobita.',
        'numeric' => ':attribute harus antara :min sampai :max.',
        'string' => ':attribute harus antara :min sampai :max karakter.',
    ],
    'boolean' => ':attribute hanya boleh berisi benar atau salah.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'date' => ':attribute bukan tanggal yang sah.',
    'date_format' => ':attribute tidak cocok dengan format :format.',
    'different' => ':attribute dan :other harus berbeda.',
    'digits' => ':attribute harus terdiri dari :digits angka.',
    'digits_between' => ':attribute harus terdiri dari :min sampai :max angka.',
    'email' => ':attribute harus berupa alamat surel yang sah.',
    'exists' => ':attribute yang dipilih tidak terdaftar.',
    'file' => ':attribute harus berupa berkas.',
    'filled' => ':attribute wajib diisi.',
    'gt' => [
        'numeric' => ':attribute harus lebih besar dari :value.',
        'file' => 'Ukuran :attribute harus lebih besar dari :value kilobita.',
        'string' => ':attribute harus lebih dari :value karakter.',
        'array' => ':attribute harus berisi lebih dari :value item.',
    ],
    'gte' => [
        'numeric' => ':attribute harus sama dengan atau lebih besar dari :value.',
        'file' => 'Ukuran :attribute harus sama dengan atau lebih besar dari :value kilobita.',
        'string' => ':attribute harus sama dengan atau lebih dari :value karakter.',
        'array' => ':attribute harus berisi :value item atau lebih.',
    ],
    'image' => ':attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak sah.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'lt' => [
        'numeric' => ':attribute harus lebih kecil dari :value.',
        'file' => 'Ukuran :attribute harus lebih kecil dari :value kilobita.',
        'string' => ':attribute harus kurang dari :value karakter.',
        'array' => ':attribute harus berisi kurang dari :value item.',
    ],
    'lte' => [
        'numeric' => ':attribute harus sama dengan atau lebih kecil dari :value.',
        'file' => 'Ukuran :attribute harus sama dengan atau lebih kecil dari :value kilobita.',
        'string' => ':attribute harus sama dengan atau kurang dari :value karakter.',
        'array' => ':attribute tidak boleh berisi lebih dari :value item.',
    ],
    'max' => [
        'array' => ':attribute tidak boleh berisi lebih dari :max item.',
        'file' => 'Ukuran :attribute tidak boleh lebih dari :max kilobita.',
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
    ],
    'mimes' => ':attribute harus berupa berkas berjenis: :values.',
    'mimetypes' => ':attribute harus berupa berkas berjenis: :values.',
    'min' => [
        'array' => ':attribute harus berisi minimal :min item.',
        'file' => 'Ukuran :attribute minimal :min kilobita.',
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
    ],
    'not_in' => ':attribute yang dipilih tidak sah.',
    'numeric' => ':attribute harus berupa angka.',
    'present' => ':attribute harus disertakan.',
    'prohibited' => ':attribute tidak boleh diisi.',
    'regex' => 'Format :attribute tidak sah.',
    'required' => ':attribute wajib diisi.',
    'required_if' => ':attribute wajib diisi bila :other adalah :value.',
    'required_with' => ':attribute wajib diisi bila ada :values.',
    'required_without' => ':attribute wajib diisi bila tidak ada :values.',
    'same' => ':attribute dan :other harus sama.',
    'size' => [
        'array' => ':attribute harus berisi :size item.',
        'file' => 'Ukuran :attribute harus :size kilobita.',
        'numeric' => ':attribute harus berukuran :size.',
        'string' => ':attribute harus :size karakter.',
    ],
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah terpakai.',
    'uploaded' => ':attribute gagal diunggah.',
    'url' => 'Format :attribute tidak sah.',

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /**
     * Nama atribut dipusatkan di sini supaya controller tak perlu mengulang
     * daftar nama pada tiap panggilan validate(). Yang di controller tetap
     * menang bila berbeda.
     */
    'attributes' => [
        'nip' => 'NIP',
        'password' => 'kata sandi',
        'kode' => 'kode',
        'gedung' => 'gedung',
        'lantai' => 'lantai',
        'lokasi_teks' => 'lokasi',
        'foto' => 'foto',
        'catatan' => 'catatan',
        'catatan_review' => 'catatan tinjauan',
        'severity' => 'tingkat keparahan',
        'target_selesai' => 'tanggal target selesai',
        'lat' => 'lintang',
        'lng' => 'bujur',
        'gps_accuracy' => 'ketelitian GPS',
    ],
];
