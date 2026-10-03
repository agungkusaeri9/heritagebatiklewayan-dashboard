# Changelog

Semua perubahan penting pada proyek ini akan didokumentasikan dalam file ini.

Format pencatatan mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.0.0/) dan mengikuti [Semantic Versioning](https://semver.org/lang/id/).

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
