# Changelog

Semua perubahan penting pada proyek ini akan didokumentasikan dalam file ini.

Format pencatatan mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.0.0/) dan mengikuti [Semantic Versioning](https://semver.org/lang/id/).

---

## [0.7.0] - 2026-10-29

Penyediaan modul dinamis Konfigurasi Aplikasi (`app_configs`) untuk integrasi pihak ketiga (RajaOngkir, Fashn AI, Geoapify), modul Fashn AI Virtual Try-On yang mencakup manajemen model avatar mannequin bawaan (`virtual_try_on_models`) serta katalog hasil simulasi try-on produk (`product_virtual_try_ons`), filter komprehensif (termasuk model default tanpa user), dan standardisasi komponen Select 100% width.

### ✨ Fitur Baru (Features)

- **Manajemen Konfigurasi Aplikasi (`app_configs`)**:
  - CRUD tabel konfigurasi aplikasi (`key`, `value`, `description`) dengan masking nilai kredensial sensitif, pencarian, dan paginasi.
  - Halaman konfigurasi **RajaOngkir**:
    - Kunci API RajaOngkir (`rajaongkir_api_key`)
    - Base URL endpoint API RajaOngkir (`rajaongkir_base_url`, default: `https://rajaongkir.komerce.id`) dengan file caching (`Cache::store('file')`) dan auto-invalidation/warming saat diperbarui di dashboard
  - Halaman konfigurasi **Fashn AI**:
    - Kunci API Fashn AI (`fashn_api_key`)
    - Base URL endpoint API Fashn AI (`fashn_api_base_url`, default: `https://api.fashn.ai`) dengan file caching dan auto-invalidation/warming saat diperbarui di dashboard
  - Halaman konfigurasi **Geoapify**:
    - Kunci API Geoapify (`geoapify_api_key`)
    - Base URL endpoint API Geoapify (`geoapify_base_url`, default: `https://api.geoapify.com`) dengan file caching dan auto-invalidation/warming saat diperbarui di dashboard
  - Penyederhanaan menu navigasi sidebar (sub-menu Semua Konfigurasi disembunyikan agar navigasi lebih terfokus pada sub-menu penyedia layanan).

- **Pengaturan Fitur & Kuota Virtual Try-On (`/fashn-ai/settings`)**:
  - Disediakan sub-menu khusus **Pengaturan** pada grup menu sidebar **Fashn AI** (`FashnAiSettingController`), memisahkan pengaturan fitur bisnis dari kredensial API koneksi:
    - Master switch aktifasi fitur Virtual Try-On (`is_product_virtual_try_on`)
    - Toggle fitur Virtual Try-On Foto (`is_product_virtual_try_on_photo`)
    - Toggle fitur Virtual Try-On Video (`is_product_virtual_try_on_video`)
    - Durasi video generasi model (`fashn_video_duration`, opsi 5 atau 10 detik, default: 5 detik) berbasis file cache
    - Resolusi video generasi model (`fashn_video_resolution`, opsi 480p, 720p, 1080p, default: 480p) berbasis file cache
    - Batas kuota harian per pengguna (`virtual_try_on_daily_limit_per_user`, default: 3)
    - Batas kuota mingguan seluruh pengguna (`virtual_try_on_weekly_limit_all_users`, default: 100)

- **Manajemen Model Mannequin / Avatar Fashn AI (`/virtual-try-on-models`)**:
  - Dukungan dua metode pembuatan model avatar: **Unggah Manual (File)** dan **Generate Otomatis via AI (Fashn AI `model-create`)**.
  - Integrasi endpoint asynchronous Fashn AI (`POST /v1/run` & `GET /v1/status/{id}`) dengan pengunduhan otomatis output gambar dari CDN Fashn AI ke direktori storage publik lokal sistem.
  - CRUD lengkap model mannequin avatar bawaan toko/sistem.
  - Atribut model: `name`, `gender` (`male`, `female`, `unisex`), `photo` (file upload atau hasil generasi AI), status `is_active`, dan penghitungan penggunaan try-on (`try_ons_count`).
  - Modal dialog pembuatan & pengeditan interaktif dengan selector file gambar, rekomendasi prompt cepat, dan preview langsung.
  - Lightbox modal untuk memperbesar dan melihat foto model dalam resolusi penuh.

- **Katalog & Pembuatan Model Default Product Virtual Try-On (`/product-virtual-try-ons`)**:
  - Penambahan fitur **Generate Model Default Virtual Try-On Otomatis via AI**:
    - Menghilangkan unggah manual file gambar dan video saat membuat model default produk.
    - Menjalankan pipeline generasi terintegrasi dengan Fashn AI API:
      - **Foto Try-On (`tryon-max`)**: Memadukan foto produk busana batik (`product_image`) dengan foto avatar model pilihan (`model_image`) secara realistis via `POST /v1/run` model `tryon-max`.
      - **Video Try-On (`image-to-video`)**: Pembuatan video simulasi gerakan model 5 detik resolusi 480p (`POST /v1/run` dengan model `image-to-video`) dari foto hasil tryon-max.
      - Mengunduh otomatis output berkas media dari CDN Fashn AI dan menyimpannya secara lokal pada storage server (`public` disk).
    - Eksekusi asynchronous via antrean worker (`GenerateProductVirtualTryOnJob`) agar proses yang memakan waktu tidak memblokir user interface.
    - Pemantauan status antrean dan progress *real-time* di tabel data (`queued`, `processing`, `completed`, `failed`) lengkap dengan animasi progress bar persentase, label langkah pengerjaan, dan auto-polling setiap 3 detik saat terdapat proses aktif.
    - Fitur **Coba Lagi (Retry)** pada generasi yang gagal tanpa menghapus record.
    - Mempertahankan aturan bisnis: **Maksimal 1 model pria dan 1 model wanita per produk** (`user_id = null`).
  - Antarmuka form modal dilengkapi kartu status kuota model default produk secara *real-time*, validasi otomatis slot gender yang masih tersedia, dropdown pemilihan model mannequin yang sesuai gender, serta toggle aktifasi generasi video (480p, 5 detik).
  - Pengelolaan katalog seluruh hasil simulasi virtual try-on busana batik (melihat daftar, detail, dan hapus).
  - Sistem penyaringan data tingkat lanjut:
    - **Filter Without User / Model Default Produk**: Menyaring hasil try-on yang difungsikan sebagai model showcase produk default (tanpa akun user).
    - **Filter Produk Batik**: Dropdown filter berdasarkan produk spesifik.
    - **Filter Pelanggan**: Dropdown filter berdasarkan akun pengguna.
    - **Pencarian Cepat**: Pencarian multi-field berdasarkan nama produk, kode SKU, nama pelanggan, maupun email.
  - Modal dialog detail lengkap yang menyajikan preview foto resolusi tinggi, pemutar video HTML5 generasi virtual try-on, data produk batik terkait, avatar model mannequin, serta identitas pelanggan.

- **API Endpoint Virtual Try-On Produk (`/api/v1/virtual-try-ons` & `/api/v1/product-virtual-try-ons`)**:
  - `GET /api/v1/virtual-try-ons`: Mengambil daftar hasil virtual try-on dengan filter multi-parameter (`product_id`, `gender`, `status`, `type` [`my`, `default`, `all`]), pencarian, dan paginasi terstruktur via `ProductVirtualTryOnResource`.
  - `GET /api/v1/virtual-try-ons/models`: Mengambil daftar model avatar mannequin aktif (`VirtualTryOnModelResource`) yang siap digunakan pelanggan untuk mencoba busana batik.
  - `POST /api/v1/virtual-try-ons`: Memulai request pembuatan virtual try-on baru untuk pelanggan terotentikasi Sanctum (`auth:sanctum`), dilengkapi validasi ketersediaan foto produk, validasi model avatar aktif, pengecekan kuota harian user (`virtual_try_on_daily_limit_per_user`), kuota mingguan sistem (`virtual_try_on_weekly_limit_all_users`), dan dispatch asynchronous job antrean.
  - `GET /api/v1/virtual-try-ons/{id}`: Menampilkan rincian lengkap hasil simulasi try-on dengan proteksi otorisasi kepemilikan akun.
  - `GET /api/v1/virtual-try-ons/{id}/progress` (dan alias `GET .../{id}/status`): Endpoint ringan khusus polling progress real-time dari aplikasi mobile/client (`status`, `progress`, `step_label`, `photo_url`, `video_url`, `is_completed`, `is_failed`).
  - `POST /api/v1/virtual-try-ons/{id}/retry`: Mengulang proses antrean untuk tugas try-on yang berstatus gagal.
  - `GET /api/v1/virtual-try-ons/user/quota`: Mengambil informasi status sisa kuota harian pengguna dan kuota mingguan sistem.
  - `GET /api/v1/products/{id}/virtual-try-ons`: Mengambil daftar virtual try-on langsung berdasarkan ID produk.

### 🏗️ Arsitektur & Backend

- **Database Migrations & Models**:
  - Migrasi `2026_10_29_021543_create_app_configs_table.php`.
  - Migrasi `2026_10_29_030356_create_virtual_try_on_models_table.php`.
  - Migrasi `2026_10_29_030400_create_product_virtual_try_ons_table.php`.
  - Migrasi `2026_10_29_032533_add_gender_to_product_virtual_try_ons_table.php` (menambahkan kolom `gender` dan indeks komposit `product_id + gender`).
  - Migrasi `2026_10_29_040823_add_status_and_progress_to_product_virtual_try_ons_table.php` (menambahkan kolom `status`, `progress`, `step_label`, `task_id_photo`, `task_id_video`, `generate_video`, `error_message`).
  - Model Eloquent `AppConfig`, `VirtualTryOnModel`, dan `ProductVirtualTryOn` dengan relasi lengkap ke `Product` dan `User`.
- **Service & Repository Pattern**:
  - Pemisahan abstraksi interface dan implementasi Eloquent untuk `AppConfig`, `VirtualTryOnModel`, dan `ProductVirtualTryOn` yang dibinding pada `AppServiceProvider`.
  - Service layer terpisah dengan otomatisasi audit logging via `ActivityLogService`.
  - Service terdedikasi `FashnAiService` untuk manajemen komunikasi API Fashn AI:
    - `runModelCreate`, `runFaceToModel`, `runImageToVideo`.
    - Polling status task Fashn AI (`checkStatus`, `waitForCompletion`).
    - Pengonversian otomatis berkas avatar lokal/localhost ke Data URI base64 agar dapat diakses server Fashn AI (`prepareImageInput`).
    - Pengunduhan otomatis gambar dan video dari CDN Fashn AI dan penyimpanan ke disk penyimpanan lokal sistem (`downloadAndStoreImage`, `downloadAndStoreVideo`).
  - Job Queue asynchronous `GenerateProductVirtualTryOnJob` untuk pemrosesan background generasi dua tahap (foto model via tryon-max lalu video model via image-to-video) dengan pelaporan progress bertahap.
  - Penyesuaian batas waktu timeout queue worker (`--timeout=600`, `--tries=3`) pada script `dev` `composer.json` dan konfigurasi `config/queue.php` (`retry_after` = 600s) untuk mendukung proses rendering video AI tanpa kendala timeout 60 detik.
  - Pembuatan `GeoapifyService` serta integrasi mekanisme file caching (`Cache::store('file')`, TTL 24 jam) pada `RajaOngkirService`, `GeoapifyService`, dan `FashnAiService` untuk pembacaan kredensial cepat dan hemat query database, dilengkapi proses pembersihan dan pemanasan cache otomatis (*cache invalidation & pre-warming*) setiap kali data konfigurasi diperbarui.
- **Hak Akses & Seeder**:
  - Pendaftaran permission Spatie: `view/create/edit/delete virtual try on models`, `view/create/edit/delete product virtual try ons`, dan `view/create/edit/delete app configs` pada `RolePermissionSeeder.php`.
  - Pembuatan `AppConfigSeeder.php` dan `VirtualTryOnSeeder.php` dengan data default avatar mannequin pria, wanita, unisex, serta contoh try-on default produk dan user.

### 🎨 Antarmuka & UX (UI / UX)

- **Menu Navigasi Fashn AI**:
  - Penambahan grup menu sidebar **Fashn AI** dengan ikon `Sparkles` berisikan sub-menu **Model**, **Product Virtual Try-On**, dan **Pengaturan**.
- **Standardisasi Lebar Komponen Select (100% Width)**:
  - Pembaruan komponen UI `SelectTrigger.vue` dari `w-fit` menjadi `w-full` agar komponen dropdown `<Select>` secara default mengisi penuh 100% lebar grid kolom form.

---

## [0.6.0] - 2026-10-28

Penyempurnaan integrasi API Riwayat & Detail Pesanan (`/api/v1/orders`), filter status pesanan (Active, Completed, Cancelled), unggah bukti bayar, serta endpoint profil pengguna (`/api/v1/user/profile`) untuk konsumsi antarmuka mobile My Orders dan Your Profile.

### ✨ Fitur Baru (Features)

- **Endpoint API Riwayat & Filter Status Pesanan (`GET /api/v1/orders`)**:
  - Mendukung penyaringan pesanan berdasarkan status (`status=processing`, `status=pending`, `status=success`, `status=failed`, atau `all`) yang dipetakan ke tab *Active*, *Completed*, dan *Cancelled* pada aplikasi mobile.
  - Mendukung paginasi data pesanan (`page`, `per_page`) serta pencarian teks (`search`).
  - Serialisasi data teroptimasi via `App\Http\Resources\OrderListResource` yang mengembalikan `total_items`, ringkasan biaya, kurir, dan informasi status pembayaran.

- **Endpoint API Detail Pesanan Lengkap (`GET /api/v1/orders/{identifier}`)**:
  - Mengembalikan rincian lengkap pesanan berdasarkan ID atau nomor pesanan (`order_number`).
  - Serialisasi data lengkap via `App\Http\Resources\OrderDetailResource` meliputi seluruh daftar produk (`items`), alamat penerima, kurir & resi pengiriman, breakdown harga (subtotal, ongkos kirim, diskon, biaya layanan, total), serta status transaksi.

- **Endpoint Unggah Bukti Pembayaran (`POST /api/v1/orders/{identifier}/payment-proof`)**:
  - Menerima unggahan file bukti transfer pembayaran (`payment_proof`) dengan validasi tipe berkas dan ukuran gambar.
  - Memperbarui status pembayaran pesanan menjadi menunggu verifikasi (*waiting verification*).

- **Endpoint API Pembaruan Profil Pengguna (`PUT|POST /api/v1/user/profile`)**:
  - Menyediakan endpoint terotentikasi Sanctum untuk memperbarui data identitas pengguna (`name`, `phone`, `gender`).
  - Mendukung unggahan multipart berkas foto profil (`avatar`) dengan penyimpanan otomatis ke direktori storage publik dan pembaruan URL avatar pengguna.

### 🏗️ Arsitektur & Backend

- **Normalisasi URL Storage Avatar & Bukti Bayar**:
  - Memastikan path berkas avatar dan bukti bayar dikembalikan dalam format URL publik yang dapat diakses oleh client mobile.
- **Konsistensi Kode Respon & Error Handling**:
  - Standarisasi format respon JSON sukses dan respon validasi kegagalan sesuai kontrak API `ApiResponse`.

---

## [0.5.0] - 2026-10-27

Penambahan endpoint API banner promosi beranda (`GET /api/v1/banners`) untuk konsumsi dinamis mobile carousel beserta resource serialisasi dan pengujian fitur.

### ✨ Fitur Baru (Features)

- **Endpoint API Banner Promosi (`GET /api/v1/banners`)**:
  - Menyajikan daftar banner promosi aktif (`is_active = true`) yang diurutkan berdasarkan prioritas `sort_order` dan tanggal pembuatan.
  - Mengembalikan metadata lengkap: judul, deskripsi/subtitle, teks tombol, tipe target navigasi (`product`, `category`, `url`, `none`), ID produk/kategori terkait, serta URL gambar.
  - Controller terisolasi `App\Http\Controllers\Api\BannerController`.
  - Serialisasi data aman menggunakan `App\Http\Resources\BannerResource`.

### 🏗️ Arsitektur & Backend

- **Rute API Publik**:
  - Pendaftaran rute publik `GET /banners` pada grup versi `api/v1` di `routes/api.php`.
- **Feature Testing**:
  - Penambahan file pengujian fitur `tests/Feature/Api/BannerApiTest.php` untuk memvalidasi respon JSON format standar, filter banner aktif, dan pengurutan `sort_order`.

---

## [0.4.0] - 2026-10-25

Penambahan endpoint API data master kategori produk, kurir pengiriman, produk rekomendasi & terlaris untuk integrasi beranda mobile, serta integrasi layanan kalkulasi ongkos kirim RajaOngkir.

### ✨ Fitur Baru (Features)

- **Endpoint API Kategori Produk (`GET /api/v1/categories`)**:
  - Menyediakan daftar kategori aktif yang diurutkan berdasarkan abjad (`name`).
  - Menghitung jumlah produk aktif per kategori (`total_items`) secara efisien via `withCount('products')`.
  - Menambahkan relasi `products(): HasMany` pada model `Category`.
  - Penyempurnaan `CategoryResource` dengan atribut `description`, `total_items`, dan `is_active`.

- **Endpoint API Produk Beranda (Rekomendasi & Terlaris)**:
  - `GET /api/v1/products/recommendations`: Mengambil produk aktif dengan prioritas fitur Virtual Try-On (`is_virtual_try_on DESC`) dan ID terbaru untuk carousel rekomendasi.
  - `GET /api/v1/products/best-sellers`: Mengambil produk terlaris dengan penghitungan ulasan (`reviews_count`) untuk bagian best seller beranda.

- **Endpoint API Kurir & Kalkulasi Ongkir (RajaOngkir / Komerce)**:
  - `GET /api/v1/couriers` & `GET /api/v1/shipping-types`: Menampilkan daftar kurir pengiriman aktif via `CourierController` dan `CourierResource`.
  - `POST /api/v1/couriers/cost`, `POST /api/v1/shipping/cost`, dan `POST /api/v1/check-ongkir`: Endpoint perhitungan ongkos kirim otomatis berdasarkan alamat tujuan dan berat paket.
  - Konfigurasi kredensial `rajaongkir` pada `config/services.php` dan template variabel environment pada `.env.example`.

### 🏗️ Arsitektur & Backend

- **Database Seeder**:
  - Pendaftaran `UserCartSeeder::class` pada `DatabaseSeeder.php` untuk inisialisasi data keranjang belanja pengujian.

---

## [0.3.0] - 2026-10-23

Pembaruan modul Pengaturan Kebijakan & Privasi (Privacy Policy Settings) dengan penambahan kolom khusus Kebijakan Pembatalan (Cancellation Policy) pada database, integrasi antarmuka form admin 3-kolom, serta penyempurnaan endpoint API untuk konsumsi aplikasi mobile.

### ✨ Fitur Baru (Features)

- **Manajemen Kebijakan Pembatalan (Cancellation Policy Management)**:
  - Penambahan kolom input form *Kebijakan Pembatalan (Cancellation Policy)* pada halaman pengaturan web admin (`/privacy-policy-settings`).
  - Antarmuka form responsif 3-kolom untuk mengelola Kebijakan Pembatalan (*Cancellation Policy*), Syarat & Ketentuan (*Terms & Policy*), dan Kebijakan Privasi (*Privacy Policy*).
  - Validasi dan penyimpanan data `cancellation_policy` pada `PrivacyPolicyController` dan `StoreSettingController`.

- **Penyempurnaan Endpoint API Kebijakan Toko (Store Policy API)**:
  - Endpoint `GET /api/v1/settings/privacy-policy` kini mengembalikan data `cancellation_policy` bersama `privacy_policy`, `policy`, dan `updated_at`.
  - Resource `StoreSettingResource` menyertakan `cancellation_policy` dalam struktur grup `policies` untuk integrasi mobile.

### 🏗️ Arsitektur & Backend

- **Migrasi Database `cancellation_policy`**:
  - Penambahan kolom `cancellation_policy` berjenis `longText` (`nullable`) pada tabel `store_settings`.
  - Pendaftaran `cancellation_policy` ke dalam `$fillable` model `App\Models\StoreSetting`.
- **Database Seeder**:
  - Pembaruan `StoreSettingSeeder` dengan template resmi ketentuan pembatalan pesanan dan batas waktu pengajuan sebelum paket diserahkan ke pihak kurir ekspedisi.
- **Unit & Feature Testing**:
  - Penambahan pengujian unit dan fitur pada `tests/Feature/PrivacyPolicyTest.php` untuk verifikasi simpan form admin dan respon payload API `cancellation_policy`.

---

## [0.2.0-alpha.1] - 2026-10-20

Pembaruan tampilan tabel pelanggan dengan penambahan kolom avatar otomatis melalui model accessor, serta penambahan endpoint API pembaruan kata sandi pengguna terautentikasi dengan verifikasi kata sandi saat ini.

### ✨ Fitur Baru (Features)

- **Kolom Avatar pada Tabel Pelanggan (Customer Avatar Column)**:
  - Penambahan kolom *Avatar* pada posisi kolom pertama di tabel data master pelanggan (`/customers`).
  - Menampilkan foto avatar pelanggan dengan fallback otomatis ke ikon avatar default (`avatar.svg`).
  - Penyesuaian layout tabel dan status kosong (*empty state colspan*).

- **Endpoint API Pembaruan Kata Sandi (Update User Password API)**:
  - Penambahan endpoint `PUT /api/v1/user/password` berotentikasi Laravel Sanctum (`auth:sanctum`).
  - Validasi ketat kata sandi saat ini menggunakan aturan bawaan Laravel (`current_password`).
  - Validasi kata sandi baru: minimal 8 karakter, konfirmasi kecocokan (`confirmed`), dan larangan menggunakan kata sandi yang sama dengan kata sandi saat ini (`different:current_password`).
  - Penyimpanan kata sandi baru terenkripsi secara aman menggunakan `Hash::make()`.

- **Endpoint API Hapus Akun (Delete Account API)**:
  - Penambahan endpoint `DELETE /api/v1/user/account` (dan alias `DELETE /api/v1/user`) berotentikasi Laravel Sanctum.
  - Penonaktifan akun dengan mengubah flag `is_deleted = true` pada tabel `users` dan `customers` (`is_active = false`).
  - Pencabutan seluruh personal access token secara otomatis untuk keamanan.
  - Proteksi login: akun dengan status `is_deleted = true` ditolak secara otomatis saat mencoba masuk.

### 🏗️ Arsitektur & Backend

- **Kolom `is_deleted` pada Database**:
  - Migration penambahan kolom boolean `is_deleted` (default `false`) pada tabel `users` dan `customers`.
  - Pendaftaran `is_deleted` pada `$fillable` dan `$casts` di model `App\Models\User` dan `App\Models\Customer`.
- **Accessor & Appends Model Customer**:
  - Penambahan atribut `avatar_url` pada array `$appends` di model `App\Models\Customer`.
  - Implementasi accessor `getAvatarUrlAttribute()` untuk menangani fallback cerdas: URL eksternal (http/https), path relatif storage publik (`Storage::disk('public')->url()`), atau default SVG lokal (`asset('assets/images/avatar.svg')`), sehingga frontend tidak perlu melakukan pengolahan URL secara manual.
- **Controller Terisolasi**:
  - Pembuatan `App\Http\Controllers\Api\UserPasswordController` untuk pembaruan kata sandi.
  - Pembuatan `App\Http\Controllers\Api\UserAccountController` untuk penghapusan akun.

---

## [vx.x.x]

Penambahan fitur **Import Produk via Excel** secara massal dan modul **Manajemen Pelanggan (Customer)** lengkap dengan *seeding* otomatis.

### ✨ Fitur Baru (Features)

- **Import Produk via Excel (.xlsx / .csv)**:
  - Integrasi pustaka `maatwebsite/excel` untuk pengimporan data produk.
  - Fitur unduh file template Excel (.xlsx) dengan kolom yang siap diisi.
  - Sistem impor pintar (*auto-create* Kategori dan Varian jika belum ada).
- **Manajemen Pelanggan (Customer)**:
  - Database terpisah untuk entitas Pelanggan (`customers`) dan Alamat (`customer_addresses`).
  - Halaman CRUD pelanggan (Daftar, Detail Profil & Alamat, Edit, dan Hapus) lengkap dengan filter pencarian dan paginasi.
  - Navigasi sidebar terdedikasi untuk "Pelanggan" di grup Data Master.

### 🏗️ Arsitektur & Backend

- **Customer Service & Repository**: Pemisahan logika dari Controller. Pengecekan eksistensi data dipusatkan di layer *Service* (`getCustomerById`).
- **Database Seeder**: Penambahan `CustomerSeeder` untuk men-generate 20 data pelanggan *dummy* beserta struktur alamatnya.
- **Resolusi Bug Hydration**: Menghapus komponen `Switch` dari Reka-UI (yang menyebabkan *mismatch*) dan menggantinya dengan Checkbox Native (*Tailwind-styled*) pada form Produk dan form Pelanggan.

---

## [vx.x.x]

Penambahan fitur **Kategori Produk** dan **Varian Produk** dengan CRUD lengkap, upload gambar untuk kategori, navigasi sidebar dropdown Produk, serta berbagai perbaikan UI.

### ✨ Fitur Baru (Features)

- **Manajemen Kategori Produk (Category CRUD)**:
  - Pengelolaan data kategori produk: Nama, Slug URL (auto-generate), Deskripsi, Gambar, dan Status Aktif.
  - Upload gambar dengan pratinjau langsung (*live preview*). Gambar otomatis terhapus saat update atau kategori dihapus.
  - Thumbnail gambar pada tabel, filter status, pencarian real-time, paginasi bernomor, dan pengaturan jumlah data.
- **Manajemen Varian Produk (Variant Mastering)**:
  - Pengelolaan Tipe Varian dinamis (misal: Ukuran, Warna, Motif).
  - Integrasi auto-generate slug dan filter status.
  - Halaman khusus (nested route) untuk mengelola nilai-nilai varian (contoh: S, M, L, XL) dengan kemampuan input cepat.

### 🏗️ Arsitektur & Backend

- **Service & Repository Pattern** untuk modul Category:
  - `CategoryRepositoryInterface` + `CategoryRepository` (Eloquent).
  - `CategoryService` dengan pengelolaan transaksi database, upload/replace/delete file gambar via `Storage::disk('public')`, auto-generate slug, dan activity logging.
  - Form Request: `IndexCategoryRequest`, `StoreCategoryRequest`, `UpdateCategoryRequest`.
  - Route Resource: `categories` terdaftar di `routes/web.php`.
- **Perbaikan Bug (Bug Fixes)**:
  - Gambar kategori tidak hilang saat update tanpa mengganti gambar baru (`unset($data['image'])` pada `CategoryService::updateCategory`).
  - Error `SelectItem value=""` pada reka-ui diatasi dengan sentinel `'all'` dan fungsi `buildQuery()`.
  - Komponen `Textarea.vue` dibuat untuk mengatasi missing import `@/components/ui/textarea`.
  - Error template parse Vue akibat `<textarea />` self-closing tag diperbaiki menjadi `></textarea>`.
- **Navigasi Sidebar**: Menu dropdown **Produk** dengan sub-menu **Kategori Produk** ditambahkan ke `AppSidebar.vue`.
- **Unit & Feature Testing**:
  - `CategoryServiceTest` (Unit) — 3 test cases.
  - `CategoryTest` (Feature) — 5 test cases mencakup CRUD lengkap dan upload gambar.

---

## [vx.x.x]

Pembaruan fitur lupa & atur ulang kata sandi mandiri via email (Fortify integration), penambahan kolom email pada pengguna dan profil, serta perombakan antarmuka autentikasi dan pengaturan akun dengan desain modern dan segar.

### ✨ Fitur Baru (Features)

- **Fitur Lupa & Atur Ulang Kata Sandi (Password Reset Flow)**:
  - Alur pemulihan kata sandi mandiri melalui pengiriman tautan ke email pengguna via Laravel Fortify.
  - Halaman permintaan pemulihan kata sandi (`ForgotPassword.vue`) dan halaman pengisian kata sandi baru (`ResetPassword.vue`).
  - Action `ResetUserPassword` untuk validasi keamanan kata sandi dan hashing password baru.
- **Dukungan Email Pengguna & Profil**:
  - Penambahan kolom `email` (nullable & unique) pada tabel `users`.
  - Integrasi input email pada form Tambah dan Edit Pengguna di sisi admin.
  - Integrasi input email pada halaman **Pengaturan Profil** pengguna (`Profile.vue`).
  - Aturan validasi email di `StoreUserRequest`, `UpdateUserRequest`, dan `ProfileValidationRules`.

### 🎨 Peningkatan Antarmuka (UI/UX Improvements)

- **Penyegaran Halaman Pengaturan Akun (`settings/Layout.vue`)**:
  - Pemindahan navigasi breadcrumb ke dalam *body content* di atas judul utama halaman.
  - Navigasi tab pengaturan sidebar responsif dengan ikon (`Profil`, `Keamanan`, `Tampilan`) dan efek pill aktif.
  - Pembungkusan form profil, keamanan, dan tampilan ke dalam kartu (*Card*) modern yang rapi.
  - Komponen **Hapus Akun** (`DeleteUser.vue`) dengan kartu beraksen peringatan (*destructive card*) dan modal konfirmasi hapus berjarak tombol lega.
  - Pilihan tema tampilan (`AppearanceTabs.vue`) dengan label Bahasa Indonesia (*Mode Terang*, *Mode Gelap*, *Ikuti Sistem*).
- **Desain Baru Halaman Autentikasi (`AuthCardLayout.vue`)**:
  - Efek pencahayaan lembut (*ambient glow accents*) pada latar belakang.
  - Kartu autentikasi terangkat (*elevated card*) dengan sudut lengkung halus (`rounded-2xl`), efek *backdrop blur*, dan bayangan lembut.
  - Badge logo aplikasi dengan aksen warna utama (*primary*).
- **Penyegaran Halaman Masuk (`Login.vue`)**:
  - Penambahan ikon informatif pada input field (ikon `User` dan `Lock`).
  - Tautan *"Lupa kata sandi?"* yang jelas serta tombol aksi dengan efek bayangan dan animasi *spinner* saat memproses.
  - Banner status notifikasi berwarna hijau (*emerald*) yang rapi.

---

## [0.1.0] - 2026-10-03

Rilis awal sistem manajemen pengguna, peran, hak akses, dan log aktivitas dengan antarmuka DataTable modern serta arsitektur backend Service-Repository.

### ✨ Fitur Utama (Features)

- **Manajemen Pengguna (Users CRUD)**:
  - Pengelolaan data pengguna (Nama, Username, Email, Password, dan Peran).
  - Fitur pencarian *real-time*, paginasi bernomor, dan pengaturan jumlah baris data per halaman.
  - Avatar inisial dinamis dan modal konfirmasi hapus pengguna.

- **Manajemen Peran & Hak Akses (Roles & Permissions)**:
  - Pembuatan dan pembaruan peran dengan multi-select hak akses (*permissions*).
  - Proteksi otomatis untuk peran `super admin` (akses penuh dan tidak dapat dihapus).
  - Tabel katalog seluruh hak akses (*permissions*) yang terdaftar dalam sistem.

- **Audit & Log Aktivitas (Activity Logs)**:
  - Pencatatan otomatis setiap aksi penting (Create, Update, Delete) beserta detail perubahan data.
  - Pencatatan informasi audit perangkat (IP Address dan User Agent/Browser).
  - Panel filter pencarian berdasarkan pengguna dan rentang tanggal.

- **Sistem Notifikasi Toast**:
  - Integrasi notifikasi toast Sonner dengan indikator warna (*rich colors*: hijau untuk sukses, merah untuk gagal/error) yang terhubung langsung dengan session flash Laravel Inertia.

- **Standardisasi UI / UX**:
  - Breadcrumbs dan judul halaman langsung terintegrasi di dalam *body content*.
  - Form pembuatan dan pengeditan dengan layout kartu *full-width*.
  - Seluruh antarmuka, pesan validasi, dan notifikasi menggunakan Bahasa Indonesia.

### 🏗️ Arsitektur & Backend

- **Service & Repository Pattern**:
  - Pemisahan *business logic* dan *query execution* pada modul User, Role, Permission, dan Activity Log.
- **Form Request Validation**:
  - Validasi input dan filter paginasi dikelompokkan dalam sub-folder fitur masing-masing (`app/Http/Requests/`).
- **Error Handling & Keamanan**:
  - Pengecekan keberadaan data sebelum operasi database.
  - Proteksi transaksi database dengan blok `try-catch` dan pencatatan log error otomatis.
- **Unit Testing**:
  - Pengujian unit untuk `UserService`, `RoleService`, dan `PermissionService`.

---
