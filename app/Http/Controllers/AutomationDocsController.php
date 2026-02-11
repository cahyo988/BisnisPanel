<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;

class AutomationDocsController extends Controller
{
    public function download(): StreamedResponse
    {
        $content = implode("\n", [
            'BISNISPANEL - PANDUAN AUTOMATION (AUTO REPLY)',
            '===========================================',
            '',
            'Ringkasan',
            '- Automation digunakan untuk membalas pesan WhatsApp secara otomatis.',
            '- Setiap rule terikat ke satu device.',
            '- Rule aktif akan dieksekusi saat pesan masuk.',
            '',
            'Cara membuat rule',
            '1) Pilih Target Device.',
            '2) Isi Keyword (kata kunci).',
            '3) Pilih Match Mode:',
            '   - Exact: harus sama persis dengan pesan masuk.',
            '   - Contains: pesan masuk mengandung keyword.',
            '4) Pilih Reply Type:',
            '   - Plain Text: isi pesan manual.',
            '   - Template Text: pilih template yang sudah disediakan.',
            '5) Isi Reply Message.',
            '6) Centang Rule aktif jika ingin langsung berjalan.',
            '7) Klik Create Rule.',
            '',
            'Contoh penggunaan',
            '- Keyword: halo | Match Mode: contains',
            '  Pesan masuk: "halo admin" -> akan dibalas.',
            '- Keyword: INFO | Match Mode: exact',
            '  Pesan masuk: "INFO" -> akan dibalas.',
            '  Pesan masuk: "INFO produk" -> tidak dibalas.',
            '',
            'Template preset',
            '- Sapaan cepat',
            '- Auto-reply jam operasional',
            '- Auto-reply info harga',
            '- Auto-reply lokasi',
            '- Balasan info promo',
            '- Auto-reply katalog',
            '- Auto-reply status pesanan',
            '- Auto-reply info pembayaran',
            '- Auto-reply minta admin',
            '- Konfirmasi dukungan',
            '',
            'Tips operasional',
            '- Gunakan keyword singkat dan jelas.',
            '- Hindari keyword yang terlalu umum agar tidak salah balas.',
            '- Uji dengan beberapa variasi pesan sebelum diaktifkan.',
            '- Jika perlu jeda atau penanganan khusus, gunakan rule terpisah.',
            '',
            'Troubleshooting',
            '- Tidak ada balasan: pastikan Rule aktif dan device terhubung.',
            '- Tidak match: cek Match Mode dan ejaan keyword.',
            '- Tidak tersimpan: periksa notifikasi error dan log Laravel.',
            '',
            'Catatan',
            '- Balasan otomatis hanya untuk pesan masuk (incoming).',
            '- Satu pesan hanya akan memicu satu rule pertama yang match.',
        ]);

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'automation-docs.txt', [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
