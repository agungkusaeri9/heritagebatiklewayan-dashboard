<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Comprehensive list of web permissions grouped by module.
     *
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        // Dashboard
        'view dashboard',
        'view admin dashboard',

        // Manajemen Pengguna (Admin Users)
        'view users',
        'create users',
        'edit users',
        'delete users',

        // Pelanggan (Customers)
        'view customers',
        'edit customers',
        'delete customers',

        // Peran & Hak Akses (Roles & Permissions)
        'view roles',
        'create roles',
        'edit roles',
        'delete roles',
        'view permissions',

        // Produk (Products)
        'view products',
        'view products by store',
        'filter products by store',
        'create products',
        'edit products',
        'delete products',
        'import products',

        // Kategori Produk (Categories)
        'view categories',
        'create categories',
        'edit categories',
        'delete categories',

        // Tipe & Nilai Varian (Variant Types)
        'view variant types',
        'create variant types',
        'edit variant types',
        'delete variant types',

        // Pesanan (Orders)
        'view orders',
        'edit orders',
        'update order status',
        'update order payment',
        'update order shipping',

        // Banner Promosi (Banners)
        'view banners',
        'create banners',
        'edit banners',
        'delete banners',
        'toggle banners',

        // Metode Pembayaran (Payment Methods)
        'view payment methods',
        'create payment methods',
        'edit payment methods',
        'delete payment methods',

        // Kurir (Couriers)
        'view couriers',
        'create couriers',
        'edit couriers',
        'delete couriers',
        'toggle couriers',

        // Lokasi Wilayah (Locations)
        'view locations',

        // Notifikasi (Notifications)
        'view notifications',
        'create notifications',
        'delete notifications',
        'manage notification settings',

        // Ulasan Produk (Product Reviews)
        'view product reviews',
        'toggle product reviews',
        'delete product reviews',

        // Pengaturan Toko (Store Settings)
        'view store settings',
        'edit store settings',

        // Toko (Stores)
        'view stores',
        'create stores',
        'edit stores',
        'delete stores',

        // Profile Toko (Admin Store / Seller)
        'view store profile',
        'edit store profile',

        // Kebijakan & Privasi (Privacy Policy)
        'view privacy policy',
        'edit privacy policy',

        // Landing Page (Contoh Virtual Try-On)
        'view landing page settings',
        'edit landing page settings',

        // FAQ (Tanya Jawab)
        'view faqs',
        'create faqs',
        'edit faqs',
        'delete faqs',

        // Manajemen Aplikasi (App Versions)
        'view app versions',
        'create app versions',
        'edit app versions',
        'delete app versions',

        // Konfigurasi Aplikasi (App Configurations)
        'view app configs',
        'create app configs',
        'edit app configs',
        'delete app configs',

        // Model Virtual Try-On (Fashn AI)
        'view virtual try on models',
        'create virtual try on models',
        'edit virtual try on models',
        'delete virtual try on models',

        // Produk Virtual Try-On (Fashn AI)
        'view product virtual try ons',
        'create product virtual try ons',
        'edit product virtual try ons',
        'delete product virtual try ons',

        // Log Aktivitas (Activity Logs)
        'view activity logs',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
