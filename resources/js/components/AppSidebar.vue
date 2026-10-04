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
import type { NavItem } from '@/types';

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Produk',
        href: '#',
        icon: Package,
        items: [
            {
                title: 'Semua Produk',
                href: '/products',
            },
            {
                title: 'Kategori Produk',
                href: '/categories',
            },
            {
                title: 'Varian Produk',
                href: '/variant-types',
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
            },
            {
                title: 'Pelanggan (Customer)',
                href: '/customers',
                icon: Users,
            },
            {
                title: 'Metode Pembayaran',
                href: '/payment-methods',
                icon: CreditCard,
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
            },
            {
                title: 'Hak Akses (Permissions)',
                href: '/permissions',
            },
        ],
    },
    {
        title: 'Log Aktivitas',
        href: '/activity',
        icon: History,
    },
]);
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
