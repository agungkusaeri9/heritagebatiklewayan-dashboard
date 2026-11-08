<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Database,
    LayoutGrid,
    Package,
    Users,
    History,
    ShieldCheck,
    CreditCard,
    Truck,
    Settings,
    ShoppingBag,
    Bell,
    Smartphone,
    SlidersHorizontal,
    Sparkles,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/composables/usePermissions';
import type { NavItem } from '@/types';

const { can, hasRole, isSuperAdmin } = usePermissions();

const page = usePage<any>();

const rawNavItems = computed<NavItem[]>(() => [
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
        badge: page.props.pendingOrdersCount ?? 0,
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
        title: 'Fashn AI',
        href: '#',
        icon: Sparkles,
        items: [
            {
                title: 'Model',
                href: '/virtual-try-on-models',
                permission: 'view virtual try on models',
            },
            {
                title: 'Product Virtual Try-On',
                href: '/product-virtual-try-ons',
                permission: 'view product virtual try ons',
            },
            {
                title: 'Pengaturan',
                href: '/fashn-ai/settings',
                permission: 'view app configs',
            },
        ],
    },
    {
        title: 'Data Master',
        href: '#',
        icon: Database,
        items: [
            {
                title: 'Pelanggan',
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
        title: 'Konfigurasi',
        href: '#',
        icon: SlidersHorizontal,
        items: [
            {
                title: 'Semua Konfigurasi',
                href: '/configurations',
                permission: 'view app configs',
            },
            {
                title: 'RajaOngkir',
                href: '/configurations/rajaongkir',
                permission: 'view app configs',
            },
            {
                title: 'Fashn AI',
                href: '/configurations/fashn-ai',
                permission: 'view app configs',
            },
            {
                title: 'Geoapify',
                href: '/configurations/geoapify',
                permission: 'view app configs',
            },
            {
                title: 'Firebase (FCM)',
                href: '/configurations/firebase',
                permission: 'view app configs',
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
                title: 'Landing Page',
                href: '/landing-page-settings',
                permission: 'view landing page settings',
            },
            {
                title: 'FAQ (Tanya Jawab)',
                href: '/faqs',
                permission: 'view faqs',
            },
            {
                title: 'Manajemen Aplikasi',
                href: '/app-versions',
                permission: 'view app versions',
            },
        ],
    },
    {
        title: 'Log Aktivitas',
        href: '/activity',
        icon: History,
        permission: 'view activity logs',
    },
]);

const checkItemAccess = (item: NavItem): boolean => {
    if (isSuperAdmin.value) {
return true;
}

    if (item.role && !hasRole(item.role)) {
return false;
}

    if (item.permission && !can(item.permission)) {
return false;
}

    return true;
};

const mainNavItems = computed<NavItem[]>(() => {
    return rawNavItems.value
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
