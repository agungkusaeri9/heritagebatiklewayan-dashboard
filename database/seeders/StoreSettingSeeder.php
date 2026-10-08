<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Province;
use App\Models\StoreSetting;
use App\Models\Subdistrict;
use Illuminate\Database\Seeder;

class StoreSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Temukan lokasi asal pengiriman default (jika tabel lokasi sudah di-seed)
        $province = Province::first();
        $city = $province ? City::where('province_id', $province->province_id)->first() : null;
        $subdistrict = $city ? Subdistrict::where('city_id', $city->city_id)->first() : null;

        $privacyPolicy = <<<'EOT'
1. PENGUMPULAN INFORMASI PRIBADI
Kami mengumpulkan informasi dari Anda saat mendaftar akun, melakukan transaksi checkout pemesanan, atau menghubungi layanan bantuan toko kami. Informasi yang kami kumpulkan mencakup:
- Informasi Akun: Nama lengkap, alamat email, nomor telepon/WhatsApp aktif.
- Informasi Pengiriman: Alamat lengkap tujuan pengiriman, kelurahan, kecamatan, kota/kabupaten, provinsi, dan kode pos.
- Informasi Transaksi: Detail barang batik yang dibeli, metode pembayaran yang dipilih, dan bukti transfer pembayaran.

2. PENGGUNAAN INFORMASI
Informasi yang kami kumpulkan digunakan untuk keperluan:
- Memproses verifikasi transaksi dan pengiriman paket pesanan Anda.
- Menghitung ongkos kirim ekspedisi secara akurat sesuai lokasi tujuan.
- Mengirimkan update status pemrosesan pesanan dan nomor resi pengiriman.
- Memberikan layanan purna jual, penanganan garansi, dan komplain pelanggan.
- Mengirimkan info promosi diskon dan koleksi motif batik terbaru (jika disetujui).

3. KEAMANAN & PERLINDUNGAN DATA
Kami berkomitmen menjaga keamanan dan kerahasiaan data pribadi pelanggan kami. Kata sandi dienkripsi dengan standar keamanan tinggi dan data transaksi diproses secara aman. Kami tidak akan menjual, menyewakan, atau menyebarkan data pribadi Anda kepada pihak luar tanpa izin, kecuali kepada mitra ekspedisi kurir dan payment gateway resmi untuk keperluan operasional pesanan.

4. PERSETUJUAN PENGGUNA
Dengan mengakses website dan bertransaksi di toko kami, Anda menyatakan telah membaca, memahami, dan menyetujui seluruh ketentuan Kebijakan Privasi ini.
EOT;

        $policy = <<<'EOT'
1. KETENTUAN PEMESANAN & PEMBAYARAN
- Pesanan yang masuk akan diproses pada hari kerja (Senin - Sabtu) setelah pembayaran terkonfirmasi.
- Batas waktu pembayaran pesanan adalah 1x24 jam setelah proses checkout. Pesanan yang belum dibayar melewati batas waktu tersebut akan dibatalkan otomatis oleh sistem.

2. KARAKTERISTIK & KUALITAS PRODUK BATIK
- Produk batik kami merupakan karya asli (batik tulis, cap, dan printing berkualitas tinggi).
- Terdapat potensi sedikit perbedaan gradasi warna antara foto katalog di layar monitor dengan fisik asli kain karena faktor pencahayaan studio foto dan kalibrasi layar gawai Anda.

3. KETENTUAN PEMBATALAN PESANAN (CANCELLATION POLICY)
- Pembatalan pesanan hanya dapat diajukan sebelum pesanan berstatus "Diproses" atau "Dikirim".
- Apabila pesanan sudah diserahkan kepada pihak ekspedisi kurir dan memiliki resi pengiriman, pesanan tidak dapat dibatalkan.

4. KEBIJAKAN PENGEMBALIAN & PENUKARAN (RETURN & REFUND POLICY)
- Pengembalian barang atau penukaran hanya diterima jika terjadi kesalahan kirim motif/ukuran atau produk memiliki cacat produksi mayor (robek/cacat kain).
- Pembeli WAJIB menyertakan rekaman VIDEO UNBOXING utuh tanpa jeda/potongan saat pertama kali membuka paket.
- Batas waktu pengajuan komplain maksimal 2x24 jam sejak status resi dinyatakan "Diterima" oleh pihak kurir.

5. PENGIRIMAN & EKSPEDISI
- Estimasi waktu pengantaran paket sepenuhnya bergantung pada jenis layanan kurir dan kota tujuan.
- Nomor resi pengiriman akan diperbarui secara otomatis di halaman riwayat pesanan setelah diserahkan ke pihak ekspedisi.
EOT;

        StoreSetting::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Toko Batik Nusantara',
                'logo' => null,
                'phone' => '0274-1234567',
                'whatsapp' => '6281234567890',
                'email' => 'cs@batiknusantara.com',
                'website' => 'https://batiknusantara.com',
                'facebook_url' => 'https://facebook.com/batiknusantara',
                'twitter_url' => 'https://x.com/batiknusantara',
                'instagram_url' => 'https://instagram.com/batiknusantara',
                'description' => 'Pusat batik nusantara autentik dan modern. Menghadirkan berbagai koleksi batik tulis, cap, dan kombinasi premium dari pengrajin terbaik Pekalongan, Solo, dan Yogyakarta.',
                'address' => 'Jl. Malioboro No. 45, Sosromenduran, Gedong Tengen, Kota Yogyakarta, Daerah Istimewa Yogyakarta 55271',
                'province_id' => $province?->province_id,
                'city_id' => $city?->city_id,
                'subdistrict_id' => $subdistrict?->subdistrict_id,
                'privacy_policy' => $privacyPolicy,
                'policy' => $policy,
            ]
        );
    }
}
