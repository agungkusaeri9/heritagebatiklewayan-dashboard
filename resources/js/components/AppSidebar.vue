<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Database,
    LayoutGrid,
    Package,
    User,
    Users,
    History,
    ShieldCheck,
    CreditCard,
    Truck,
    MapPin,
    Settings,
    ShoppingBag,
    Bell,
    Smartphone,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { usePermissions } from '@/composables/usePermissions';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const { can, hasRole, isSuperAdmin } = usePermissions();

const rawNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        permission: 'view dashboard',
    },
    {
        title: 'Pesanan',
        href: '/orders',
        icon: ShoppingBag,
        permission: 'view orders',
    },
    {
        title: 'Banner Promosi',
        href: '/banners',
        icon: Smartphone,
        permission: 'view banners',
    },
    {
        title: 'Produk',
        href: '#',
        icon: Package,
        items: [
            {
                title: 'Semua Produk',
                href: '/products',
                permission: 'view products',
            },
            {
                title: 'Kategori Produk',
                href: '/categories',
                permission: 'view categories',
            },
            {
                title: 'Varian Produk',
                href: '/variant-types',
                permission: 'view variant types',
            },
        ],
    },
    {
        title: 'Data Master',
        href: '#',
        icon: Database,
        items: [
            {
                title: 'Pengguna (Admin)',
                href: '/users',
                icon: User,
                permission: 'view users',
            },
            {
                title: 'Pelanggan (Customer)',
                href: '/customers',
                icon: Users,
                permission: 'view customers',
            },
            {
                title: 'Metode Pembayaran',
                href: '/payment-methods',
                icon: CreditCard,
                permission: 'view payment methods',
            },
            {
                title: 'Kurir (RajaOngkir)',
                href: '/couriers',
                icon: Truck,
                permission: 'view couriers',
            },
            {
                title: 'Lokasi',
                href: '/locations',
                icon: MapPin,
                permission: 'view locations',
            },
        ],
    },
    {
        title: 'Peran & Hak Akses',
        href: '#',
        icon: ShieldCheck,
        items: [
            {
                title: 'Peran (Roles)',
                href: '/roles',
                permission: 'view roles',
            },
            {
                title: 'Hak Akses (Permissions)',
                href: '/permissions',
                permission: 'view permissions',
            },
        ],
    },
    {
        title: 'Notifikasi',
        href: '#',
        icon: Bell,
        items: [
            {
                title: 'Daftar Notifikasi',
                href: '/notifications',
                permission: 'view notifications',
            },
            {
                title: 'Template Pengaturan',
                href: '/notification-settings',
                permission: 'manage notification settings',
            },
        ],
    },
    {
        title: 'Pengaturan',
        href: '#',
        icon: Settings,
        items: [
            {
                title: 'Pengaturan Toko',
                href: '/store-settings',
                permission: 'view store settings',
            },
            {
                title: 'Kebijakan & Privasi',
                href: '/privacy-policy-settings',
                permission: 'view privacy policy',
            },
            {
                title: 'FAQ (Tanya Jawab)',
                href: '/faqs',
                permission: 'view faqs',
            },
        ],
    },
    {
        title: 'Log Aktivitas',
        href: '/activity',
        icon: History,
        permission: 'view activity logs',
    },
];

const checkItemAccess = (item: NavItem): boolean => {
    if (isSuperAdmin.value) return true;
    if (item.role && !hasRole(item.role)) return false;
    if (item.permission && !can(item.permission)) return false;
    return true;
};

const mainNavItems = computed<NavItem[]>(() => {
    return rawNavItems
        .map((item) => {
            if (item.items && item.items.length > 0) {
                const filteredSubItems = item.items.filter((sub) => checkItemAccess(sub));
                if (filteredSubItems.length === 0) {
                    return null;
                }
                return {
                    ...item,
                    items: filteredSubItems,
                };
            }
            return checkItemAccess(item) ? item : null;
        })
        .filter((item): item is NavItem => item !== null);
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
