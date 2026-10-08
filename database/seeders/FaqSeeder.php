<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            [
                'title' => 'Bagaimana cara memesan batik di toko ini?',
                'description' => 'Pilih produk batik yang Anda inginkan, tentukan varian ukuran/motif, klik tombol "Tambah ke Keranjang" atau "Beli Sekarang", lalu lanjutkan ke proses checkout dengan mengisi alamat pengiriman dan memilih metode pembayaran yang tersedia.',
                'category' => 'Pemesanan',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Apakah batik yang dijual merupakan batik asli (tulis/cap)?',
                'description' => 'Ya, kami menyediakan berbagai jenis batik autentik meliputi Batik Tulis Asli (Handmade), Batik Cap, dan Batik Kombinasi berkualitas tinggi. Detail jenis batik dan teknik pembuatan tercantum lengkap pada deskripsi masing-masing produk.',
                'category' => 'Produk Batik',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Bagaimana cara merawat dan mencuci pakaian batik agar warnanya awet?',
                'description' => 'Cuci batik menggunakan sabun khusus lerak atau sampo bayi. Hindari mencuci menggunakan deterjen keras atau mesin cuci berkecepatan tinggi. Jemur di tempat teduh yang tidak terpapar sinar matahari langsung dan setrika dengan suhu sedang dengan melapisi kain di atasnya.',
                'category' => 'Produk Batik',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Metode pembayaran apa saja yang didukung?',
                'description' => 'Kami mendukung berbagai saluran pembayaran otomatis dan aman, termasuk Transfer Bank (BCA, Mandiri, BNI, BRI Virtual Account), E-Wallet (GoPay, OVO, ShopeePay), dan QRIS.',
                'category' => 'Pembayaran',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'Berapa lama waktu pengiriman pesanan sampai ke tujuan?',
                'description' => 'Pesanan diproses dan diserahkan ke pihak ekspedisi dalam 1-2 hari kerja. Estimasi pengiriman untuk pulau Jawa adalah 1-3 hari kerja, sedangkan luar pulau Jawa berkisar 3-5 hari kerja tergantung kurir yang dipilih (JNE, POS Indonesia, TIKI).',
                'category' => 'Pengiriman',
                'order' => 5,
                'is_active' => true,
            ],
            [
                'title' => 'Bagaimana cara melacak resi pengiriman pesanan saya?',
                'description' => 'Nomor resi otomatis diperbarui pada riwayat pesanan di akun Anda setelah paket diserahkan ke kurir. Anda juga akan menerima notifikasi status pesanan terkini.',
                'category' => 'Pengiriman',
                'order' => 6,
                'is_active' => true,
            ],
            [
                'title' => 'Bagaimana jika batik yang saya terima salah ukuran atau motif?',
                'description' => 'Anda berhak mengajukan penukaran atau retur barang. Hubungi layanan pelanggan kami maksimal 2x24 jam sejak paket diterima dengan menyertakan bukti rekaman video unboxing tanpa jeda.',
                'category' => 'Retur & Komplain',
                'order' => 7,
                'is_active' => true,
            ],
            [
                'title' => 'Apakah bisa memesan batik seragam untuk kantor atau acara pernikahan?',
                'description' => 'Tentu bisa! Kami melayani pemesanan kain dan kemeja/dress batik seragam dalam jumlah besar (custom order). Silakan hubungi admin kami melalui tombol WhatsApp yang tertera di website untuk penawaran harga khusus.',
                'category' => 'Pemesanan',
                'order' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['title' => $faq['title']],
                $faq
            );
        }
    }
}
