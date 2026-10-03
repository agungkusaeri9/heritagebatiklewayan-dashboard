<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Edit,
    Eye,
    Filter,
    Loader2,
    MoreVertical,
    Search,
    Trash2,
    ChevronLeft,
    ChevronRight,
    Users
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Customer {
    id: number;
    phone: string | null;
    is_active: boolean;
    user: {
        id: number;
        name: string;
        email: string;
    };
    created_at: string;
}

interface PaginationData {
    data: Customer[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    customers: PaginationData;
    filters: {
        search?: string;
        status?: string;
        sort?: string;
        direction?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Customer', href: '/customers' },
];

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const perPage = ref(props.filters.per_page?.toString() || '10');
const isLoading = ref(false);

let searchTimeout: ReturnType<typeof setTimeout>;

const fetchCustomers = () => {
    isLoading.value = true;
    router.get('/customers', {
        search: search.value,
        status: status.value,
        per_page: perPage.value
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => (isLoading.value = false),
    });
};

watch(search, (newVal, oldVal) => {
    if (newVal !== oldVal) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchCustomers, 400);
    }
});

watch([status, perPage], () => {
    fetchCustomers();
});

// Delete Confirmation
const isDeleteDialogOpen = ref(false);
const customerToDelete = ref<Customer | null>(null);

const confirmDelete = (customer: Customer) => {
    customerToDelete.value = customer;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (customerToDelete.value) {
        router.delete(`/customers/${customerToDelete.value.id}`, {
            onSuccess: () => {
                isDeleteDialogOpen.value = false;
                customerToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Manajemen Customer" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Customer</h1>
                </div>
                <p class="text-sm text-muted-foreground">
                    Kelola data pelanggan, profil, dan alamat.
                </p>
            </div>
        </div>

        <div class="rounded-xl border bg-card shadow-xs">
            <!-- Table Controls -->
            <div class="flex flex-col gap-4 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-1 items-center gap-2 sm:max-w-xs">
                    <div class="relative w-full">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Cari nama, email, atau no HP..."
                            class="w-full bg-background pl-8"
                        />
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-2 border-r pr-2 mr-2">
                        <Filter class="h-4 w-4 text-muted-foreground" />
                        <Select v-model="status">
                            <SelectTrigger class="h-9 w-[130px] bg-background">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                <SelectItem value="active">Aktif</SelectItem>
                                <SelectItem value="inactive">Nonaktif</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="relative overflow-x-auto">
                <div v-if="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-background/50 backdrop-blur-sm">
                    <Loader2 class="h-8 w-8 animate-spin text-primary" />
                </div>
                <table class="w-full caption-bottom text-sm">
                    <thead class="bg-muted/50 [&_tr]:border-b">
                        <tr class="border-b transition-colors">
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Nama</th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Email</th>
                            <th class="h-10 px-4 text-left align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">No. HP</th>
                            <th class="h-10 px-4 text-center align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Status</th>
                            <th class="h-10 px-4 text-right align-middle text-xs font-semibold tracking-wider text-muted-foreground uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr v-if="customers.data.length === 0">
                            <td colspan="5" class="h-32 text-center text-muted-foreground">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <Users class="h-8 w-8 text-muted-foreground/50" />
                                    <p>Tidak ada data customer ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="customer in customers.data"
                            :key="customer.id"
                            class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted"
                        >
                            <td class="p-4 align-middle font-medium">
                                {{ customer.user?.name }}
                            </td>
                            <td class="p-4 align-middle text-muted-foreground">
                                {{ customer.user?.email }}
                            </td>
                            <td class="p-4 align-middle text-muted-foreground">
                                {{ customer.phone || '-' }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                <Badge :variant="customer.is_active ? 'default' : 'secondary'">
                                    {{ customer.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-8 w-8">
                                            <MoreVertical class="h-4 w-4" />
                                            <span class="sr-only">Buka menu</span>
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-[160px]">
                                        <DropdownMenuItem as-child>
                                            <Link :href="`/customers/${customer.id}`" class="flex items-center cursor-pointer">
                                                <Eye class="mr-2 h-4 w-4" />
                                                Lihat Detail
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem as-child>
                                            <Link :href="`/customers/${customer.id}/edit`" class="flex items-center cursor-pointer">
                                                <Edit class="mr-2 h-4 w-4" />
                                                Edit Data
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="confirmDelete(customer)"
                                            class="text-destructive focus:text-destructive cursor-pointer"
                                        >
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Hapus Customer
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between border-t px-4 py-4" v-if="customers.data.length > 0">
                <div class="text-sm text-muted-foreground">
                    Menampilkan <span class="font-medium">{{ customers.from }}</span> - <span class="font-medium">{{ customers.to }}</span> dari <span class="font-medium">{{ customers.total }}</span> data
                </div>
                <div class="flex items-center space-x-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!customers.links[0].url"
                        as-child
                    >
                        <Link :href="customers.links[0].url || '#'" preserve-scroll preserve-state>
                            <ChevronLeft class="h-4 w-4 mr-1" />
                            Prev
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!customers.links[customers.links.length - 1].url"
                        as-child
                    >
                        <Link :href="customers.links[customers.links.length - 1].url || '#'" preserve-scroll preserve-state>
                            Next
                            <ChevronRight class="h-4 w-4 ml-1" />
                        </Link>
                    </Button>
                </div>
            </div>
        </div>

        <AlertDialog v-model:open="isDeleteDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Customer?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Tindakan ini tidak dapat dibatalkan. Data customer <strong>{{ customerToDelete?.user?.name }}</strong> beserta akun dan seluruh alamatnya akan dihapus permanen.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction @click="executeDelete" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                        Hapus Permanen
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
