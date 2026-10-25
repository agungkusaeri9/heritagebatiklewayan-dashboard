# Changelog

Semua perubahan penting pada proyek ini akan didokumentasikan dalam file ini.

Format pencatatan mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.0.0/) dan mengikuti [Semantic Versioning](https://semver.org/lang/id/).

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
