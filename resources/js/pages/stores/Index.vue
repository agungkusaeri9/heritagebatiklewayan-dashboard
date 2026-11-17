<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    Edit,
    Plus,
    Search,
    Trash2,
    AlertTriangle,
    Store as StoreIcon,
    X,
    ChevronLeft,
    ChevronRight,
    Mail,
    Phone,
    MessageCircle,
    UserCheck,
} from 'lucide-vue-next';
import { ref, watch, reactive } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/composables/usePermissions';
import type { BreadcrumbItem } from '@/types';

const { can } = usePermissions();

interface StoreOwner {
    id: number;
    name: string;
    username: string;
    email: string | null;
    roles: string[];
}

interface StoreItem {
    id: number;
    user_id: number | null;
    name: string;
    email: string | null;
    phone: string | null;
    whatsapp: string | null;
    logo: string | null;
    logo_url: string | null;
    description: string | null;
    created_at: string | null;
    updated_at: string | null;
    user: StoreOwner | null;
}

interface Props {
    stores: {
        data: StoreItem[];
        links: any[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Toko', href: '/dashboard/stores' },
];

const filterState = reactive({
    search: props.filters.search || '',
    per_page: props.filters.per_page || '10',
});

const debouncedSearch = useDebounceFn(() => {
    router.get(
        '/dashboard/stores',
        { ...filterState },
        { preserveState: true, replace: true },
    );
}, 300);

watch(
    () => ({ ...filterState }),
    () => {
        debouncedSearch();
    },
);

const clearSearch = () => {
    filterState.search = '';
};

const isDeleteDialogOpen = ref(false);
const storeToDelete = ref<StoreItem | null>(null);

const confirmDelete = (store: StoreItem) => {
    storeToDelete.value = store;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (storeToDelete.value) {
        router.delete(`/dashboard/stores/${storeToDelete.value.id}`, {
            onSuccess: () => {
                isDeleteDialogOpen.value = false;
                storeToDelete.value = null;
            },
        });
    }
};

const formatDateTime = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const perPageOptions = ['10', '20', '30', '40', '50', '100'];
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Manajemen Toko" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                    Manajemen Toko
                </h1>
                <p class="text-sm text-muted-foreground">
                    Kelola data toko, kontak, logo, deskripsi, serta akun Seller (Admin Store).
                </p>
            </div>

            <Button v-if="can('create stores')" as-child class="shrink-0">
                <Link href="/dashboard/stores/create">
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Toko
                </Link>
            </Button>
        </div>

        <div class="rounded-xl border bg-card shadow-xs">
            <div
                class="flex flex-col gap-4 border-b p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <span>Tampilkan</span>
                    <Select v-model="filterState.per_page">
                        <SelectTrigger class="h-8 w-[72px] text-xs">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in perPageOptions"
                                :key="opt"
                                :value="opt"
                            >
                                {{ opt }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <span>data</span>
                </div>

                <div class="relative w-full max-w-xs sm:w-72">
                    <Search
                        class="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground"
                    />
                    <Input
                        v-model="filterState.search"
                        type="search"
                        placeholder="Cari nama toko, kontak, atau seller..."
                        class="h-8 pr-8 pl-8 text-xs"
                    />
                    <button
                        v-if="filterState.search"
                        type="button"
                        class="absolute top-2 right-2 text-muted-foreground hover:text-foreground"
                        @click="clearSearch"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div class="relative w-full overflow-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="bg-muted/50 [&_tr]:border-b">
                        <tr class="border-b transition-colors">
                            <th
                                class="h-10 w-14 px-4 text-center align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                No
                            </th>
                            <th
                                class="h-10 w-20 px-4 text-center align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Logo
                            </th>
                            <th
                                class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Toko &amp; Deskripsi
                            </th>
                            <th
                                class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Kontak
                            </th>
                            <th
                                class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Seller / Admin Store
                            </th>
                            <th
                                class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Dibuat / Diperbarui
                            </th>
                            <th
                                class="h-10 px-4 text-right align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border [&_tr:last-child]:border-0">
                        <tr
                            v-for="(store, index) in stores.data"
                            :key="store.id"
                            class="transition-colors hover:bg-muted/40"
                        >
                            <td class="p-3.5 text-center align-middle text-xs font-medium text-muted-foreground">
                                {{ (stores.from || 1) + index }}
                            </td>
                            <td class="p-3.5 text-center align-middle">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg border bg-muted/40 shadow-2xs">
                                    <img
                                        v-if="store.logo_url"
                                        :src="store.logo_url"
                                        :alt="store.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <StoreIcon
                                        v-else
                                        class="h-5 w-5 text-muted-foreground/60"
                                    />
                                </div>
                            </td>
                            <td class="max-w-[260px] p-3.5 align-middle">
                                <div class="flex flex-col gap-1">
                                    <span class="font-semibold text-foreground">{{ store.name }}</span>
                                    <p class="line-clamp-2 text-xs text-muted-foreground">
                                        {{ store.description || '-' }}
                                    </p>
                                </div>
                            </td>
                            <td class="p-3.5 align-middle text-xs text-muted-foreground">
                                <div class="flex flex-col gap-1">
                                    <div v-if="store.email" class="flex items-center gap-1.5">
                                        <Mail class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                        <span class="text-foreground">{{ store.email }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <Phone class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                        <span>{{ store.phone || '-' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <MessageCircle class="h-3.5 w-3.5 shrink-0 text-emerald-600" />
                                        <span>{{ store.whatsapp || '-' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5 align-middle">
                                <div v-if="store.user" class="flex flex-col gap-1">
                                    <div class="flex items-center gap-1.5">
                                        <UserCheck class="h-3.5 w-3.5 shrink-0 text-primary" />
                                        <span class="text-xs font-medium text-foreground">{{ store.user.name }}</span>
                                    </div>
                                    <span class="text-[11px] text-muted-foreground">
                                        @{{ store.user.username }}
                                    </span>
                                    <div class="flex flex-wrap gap-1">
                                        <Badge
                                            v-for="role in store.user.roles"
                                            :key="role"
                                            variant="secondary"
                                            class="text-[10px] capitalize"
                                        >
                                            {{ role }}
                                        </Badge>
                                    </div>
                                </div>
                                <Badge v-else variant="outline" class="text-[11px] text-muted-foreground">
                                    Belum ada seller
                                </Badge>
                            </td>
                            <td class="p-3.5 align-middle text-xs text-muted-foreground">
                                <div class="flex flex-col gap-1 whitespace-nowrap">
                                    <span>
                                        <strong class="font-medium text-foreground">Dibuat:</strong>
                                        {{ formatDateTime(store.created_at) }}
                                    </span>
                                    <span>
                                        <strong class="font-medium text-foreground">Diperbarui:</strong>
                                        {{ formatDateTime(store.updated_at) }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-3.5 text-right align-middle">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        v-if="can('edit stores')"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-muted-foreground hover:text-foreground"
                                        as-child
                                        title="Edit Toko"
                                    >
                                        <Link :href="`/dashboard/stores/${store.id}/edit`">
                                            <Edit class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="can('delete stores')"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-muted-foreground hover:text-destructive"
                                        title="Hapus Toko"
                                        @click="confirmDelete(store)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="stores.data.length === 0">
                            <td colspan="7" class="p-10 text-center">
                                <div class="flex flex-col items-center justify-center gap-2 text-muted-foreground">
                                    <div class="rounded-full bg-muted p-3">
                                        <StoreIcon class="h-6 w-6" />
                                    </div>
                                    <p class="font-medium text-foreground">Data toko tidak ditemukan</p>
                                    <p class="text-xs">
                                        {{ filterState.search ? 'Coba ubah kata kunci pencarian Anda.' : 'Belum ada data toko yang terdaftar.' }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="flex flex-col gap-3 border-t p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ stores.from || 0 }} sampai {{ stores.to || 0 }} dari
                    {{ stores.total }} data
                </div>

                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="(link, i) in stores.links" :key="i">
                        <Button
                            v-if="i === 0"
                            variant="outline"
                            size="icon"
                            class="h-8 w-8"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url, { ...filterState }, { preserveState: true })"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        <Button
                            v-else-if="i === stores.links.length - 1"
                            variant="outline"
                            size="icon"
                            class="h-8 w-8"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url, { ...filterState }, { preserveState: true })"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                        <span
                            v-else-if="link.label === '...'"
                            class="px-2 text-xs text-muted-foreground"
                        >
                            ...
                        </span>
                        <Button
                            v-else
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            class="h-8 min-w-8 px-2.5 text-xs font-medium"
                            :disabled="!link.url"
                            @click="link.url && router.get(link.url, { ...filterState }, { preserveState: true })"
                        >
                            {{ link.label }}
                        </Button>
                    </template>
                </div>
            </div>
        </div>

        <Dialog
            :open="isDeleteDialogOpen"
            @update:open="isDeleteDialogOpen = $event"
        >
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <div class="mb-2 flex items-center gap-2 text-destructive">
                        <AlertTriangle class="h-5 w-5" />
                        <DialogTitle>Konfirmasi Hapus Toko</DialogTitle>
                    </div>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus toko
                        <strong>{{ storeToDelete?.name }}</strong>? Data toko dan logo akan dihapus secara permanen.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-3 sm:gap-3">
                    <Button
                        variant="outline"
                        @click="isDeleteDialogOpen = false"
                    >
                        Batal
                    </Button>
                    <Button
                        variant="destructive"
                        @click="executeDelete"
                    >
                        Hapus Toko
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
