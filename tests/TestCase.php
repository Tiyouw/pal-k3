<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Halaman publik memakai @vite. Tanpa ini tes gagal dengan
        // ViteManifestNotFoundException setiap kali `npm run build` belum
        // dijalankan, padahal yang diuji adalah keluaran peladen.
        $this->withoutVite();
    }
}
