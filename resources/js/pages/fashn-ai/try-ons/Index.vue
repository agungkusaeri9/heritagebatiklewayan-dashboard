<script setup lang="ts">
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { usePermissions } from '@/composables/usePermissions';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    AlertTriangle,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Clock,
    ExternalLink,
    Eye,
    Filter,
    Image as ImageIcon,
    Loader2,
    Plus,
    RefreshCw,
    RotateCcw,
    Search,
    Shirt,
    Sparkles,
    Trash2,
    User as UserIcon,
    Video,
    X,
    XCircle,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';

const { can } = usePermissions();

interface ProductInfo {
    id: number;
    name: string;
    slug: string;
    sku: string | null;
    price: string;
    primary_image_url: string | null;
}

interface VirtualModelInfo {
    id: number;
    name: string;
    gender: 'male' | 'female' | 'unisex';
    photo_url: string | null;
}

interface UserInfo {
    id: number;
    name: string;
    email: string;
}

interface ProductVirtualTryOn {
    id: number;
    product_id: number;
    product: ProductInfo | null;
    virtual_try_on_model_id: number | null;
    virtual_model: VirtualModelInfo | null;
    user_id: number | null;
    user: UserInfo | null;
    gender?: 'male' | 'female' | 'unisex' | null;
    photo: string | null;
    photo_url: string | null;
    video: string | null;
    video_url: string | null;
    status: 'queued' | 'processing' | 'completed' | 'failed';
    progress: number;
    step_label: string | null;
    task_id_photo?: string | null;
    task_id_video?: string | null;
    generate_video?: boolean;
    error_message?: string | null;
    is_default: boolean;
    created_at: string | null;
}

interface Pagination {
    data: ProductVirtualTryOn[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface ProductOption {
    id: number;
    name: string;
    sku: string | null;
    price: string;
    primary_image_url: string | null;
    has_default_male: boolean;
    has_default_female: boolean;
}

interface ModelOption {
    id: number;
    name: string;
    gender: 'male' | 'female' | 'unisex';
    photo_url: string | null;
}

interface Props {
    tryOns: Pagination;
    filters: {
        search?: string;
        product_id?: string;
        user_id?: string;
        user_type?: string;
        model_id?: string;
        per_page?: string;
    };
    products: ProductOption[];
    activeModels: ModelOption[];
    users: Array<{ id: number; name: string; email: string }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fashn AI', href: '#' },
    { title: 'Product Virtual Try-On', href: '/product-virtual-try-ons' },
];

const filterState = reactive({
    search: props.filters.search || '',
    user_type: props.filters.user_type || 'all',
    product_id: props.filters.product_id ? String(props.filters.product_id) : 'all',
    user_id: props.filters.user_id ? String(props.filters.user_id) : 'all',
    per_page: props.filters.per_page || '10',
});

const buildQuery = () => ({
    search: filterState.search || undefined,
    user_type: filterState.user_type === 'all' ? undefined : filterState.user_type,
    product_id: filterState.product_id === 'all' ? undefined : filterState.product_id,
    user_id: filterState.user_id === 'all' ? undefined : filterState.user_id,
    per_page: filterState.per_page === '10' ? undefined : filterState.per_page,
});

const debouncedSearch = useDebounceFn(() => {
    router.get('/product-virtual-try-ons', buildQuery(), {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(
    () => ({ ...filterState }),
    () => {
        debouncedSearch();
    }
);

const resetFilters = () => {
    filterState.search = '';
    filterState.user_type = 'all';
    filterState.product_id = 'all';
    filterState.user_id = 'all';
};

const hasActiveFilters = computed(() => {
    return (
        filterState.search !== '' ||
        filterState.user_type !== 'all' ||
        filterState.product_id !== 'all' ||
        filterState.user_id !== 'all'
    );
});

// Automatic Polling when any job is queued or processing
let pollInterval: ReturnType<typeof setInterval> | null = null;

const hasRunningJobs = computed(() => {
    return props.tryOns.data.some(
        item => item.status === 'queued' || item.status === 'processing'
    );
});

const startPolling = () => {
    if (pollInterval) return;
    pollInterval = setInterval(() => {
        if (hasRunningJobs.value) {
            router.reload({
                only: ['tryOns', 'products'],
            });
        } else {
            stopPolling();
        }
    }, 3000);
};

const stopPolling = () => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
};

watch(
    hasRunningJobs,
    running => {
        if (running) {
            startPolling();
        } else {
            stopPolling();
        }
    },
    { immediate: true }
);

onMounted(() => {
    if (hasRunningJobs.value) {
        startPolling();
    }
});

onUnmounted(() => {
    stopPolling();
});

// Create Default Model Modal (Fashn AI Integration without file upload)
const isCreateDialogOpen = ref(false);

const createForm = useForm({
    product_id: '',
    virtual_try_on_model_id: '',
    gender: '' as 'male' | 'female' | '',
    generate_video: true,
});

const selectedProduct = computed(() => {
    if (!createForm.product_id) return null;
    return props.products.find(p => String(p.id) === String(createForm.product_id)) || null;
});

const isMaleAvailable = computed(() => {
    if (!selectedProduct.value) return true;
    return !selectedProduct.value.has_default_male;
});

const isFemaleAvailable = computed(() => {
    if (!selectedProduct.value) return true;
    return !selectedProduct.value.has_default_female;
});

const isAllQuotaFull = computed(() => {
    if (!selectedProduct.value) return false;
    return !isMaleAvailable.value && !isFemaleAvailable.value;
});

// Filter models based on chosen gender
const filteredModels = computed(() => {
    if (!createForm.gender) return props.activeModels;
    return props.activeModels.filter(m => m.gender === createForm.gender || m.gender === 'unisex');
});

const selectedModelInfo = computed(() => {
    if (!createForm.virtual_try_on_model_id) return null;
    return props.activeModels.find(m => String(m.id) === String(createForm.virtual_try_on_model_id)) || null;
});

// When product changes, adjust gender if needed
watch(
    () => createForm.product_id,
    () => {
        createForm.virtual_try_on_model_id = '';
        if (selectedProduct.value) {
            if (isMaleAvailable.value && !isFemaleAvailable.value) {
                createForm.gender = 'male';
            } else if (isFemaleAvailable.value && !isMaleAvailable.value) {
                createForm.gender = 'female';
            } else if (isAllQuotaFull.value) {
                createForm.gender = '';
            }
        }
    }
);

// When gender changes, clear model if not matching
watch(
    () => createForm.gender,
    () => {
        if (selectedModelInfo.value && selectedModelInfo.value.gender !== 'unisex' && selectedModelInfo.value.gender !== createForm.gender) {
            createForm.virtual_try_on_model_id = '';
        }
    }
);

const openCreateDialog = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.generate_video = true;
    isCreateDialogOpen.value = true;
};

const submitCreate = () => {
    createForm.post('/product-virtual-try-ons', {
        preserveScroll: true,
        onSuccess: () => {
            isCreateDialogOpen.value = false;
            createForm.reset();
            startPolling();
        },
    });
};

// Retry handler
const retryingId = ref<number | null>(null);

const handleRetry = (item: ProductVirtualTryOn) => {
    retryingId.value = item.id;
    router.post(`/product-virtual-try-ons/${item.id}/retry`, {}, {
        preserveScroll: true,
        onFinish: () => {
            retryingId.value = null;
            startPolling();
        },
    });
};

// Detail Modal
const isDetailOpen = ref(false);
const activeDetail = ref<ProductVirtualTryOn | null>(null);
const activeMediaTab = ref<'photo' | 'video'>('photo');

const openDetailModal = (item: ProductVirtualTryOn) => {
    activeDetail.value = item;
    activeMediaTab.value = item.photo_url ? 'photo' : 'video';
    isDetailOpen.value = true;
};

// Delete Dialog
const isDeleteDialogOpen = ref(false);
const itemToDelete = ref<ProductVirtualTryOn | null>(null);

const confirmDelete = (item: ProductVirtualTryOn) => {
    itemToDelete.value = item;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (!itemToDelete.value) return;

    router.delete(`/product-virtual-try-ons/${itemToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            itemToDelete.value = null;
        },
    });
};

const formatPrice = (val: string | number | undefined) => {
    if (!val) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(val));
};

const formatDate = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const perPageOptions = ['10', '20', '30', '50'];
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 bg-muted/20">
        <Head title="Product Virtual Try-On - Fashn AI" />

        <!-- Breadcrumbs -->
        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Page Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Sparkles class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-semibold tracking-tight">Product Virtual Try-On</h1>
                            <Badge v-if="hasRunningJobs" variant="outline" class="bg-blue-500/10 text-blue-600 border-blue-200 animate-pulse text-[11px] gap-1">
                                <Loader2 class="h-3 w-3 animate-spin" />
                                Antrean Berjalan
                            </Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Kelola model default produk (maks. 1 pria & 1 wanita per produk via antrean AI) serta pantau simulasi try-on pelanggan
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tombol Tambah Model Default -->
            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    @click="router.reload({ only: ['tryOns', 'products'] })"
                    title="Segarkan data"
                >
                    <RefreshCw class="h-4 w-4" />
                </Button>
                <Button
                    v-if="can('create product virtual try ons')"
                    @click="openCreateDialog"
                    class="shrink-0"
                >
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Model Default
                </Button>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="rounded-xl border bg-card p-4 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm font-medium">
                    <Filter class="h-4 w-4 text-muted-foreground" />
                    <span>Filter & Pencarian</span>
                </div>
                <Button
                    v-if="hasActiveFilters"
                    variant="ghost"
                    size="sm"
                    class="h-7 text-xs text-muted-foreground hover:text-foreground"
                    @click="resetFilters"
                >
                    <RotateCcw class="mr-1.5 h-3.5 w-3.5" />
                    Reset Filter
                </Button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="filterState.search"
                        placeholder="Cari produk / pengguna..."
                        class="h-9 pl-8 pr-8 text-sm"
                    />
                    <button
                        v-if="filterState.search"
                        @click="filterState.search = ''"
                        class="absolute right-2.5 top-2.5 text-muted-foreground hover:text-foreground"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Filter Tipe Sumber / Without User -->
                <Select v-model="filterState.user_type">
                    <SelectTrigger class="w-full h-9 text-sm">
                        <SelectValue placeholder="Semua Sumber" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Sumber</SelectItem>
                        <SelectItem value="without_user">Model Default Produk (Tanpa User)</SelectItem>
                        <SelectItem value="with_user">Dicoba Pengguna (Customer)</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Filter Produk -->
                <Select v-model="filterState.product_id">
                    <SelectTrigger class="w-full h-9 text-sm truncate">
                        <SelectValue placeholder="Pilih Produk" />
                    </SelectTrigger>
                    <SelectContent class="max-h-60">
                        <SelectItem value="all">Semua Produk</SelectItem>
                        <SelectItem
                            v-for="prod in products"
                            :key="prod.id"
                            :value="String(prod.id)"
                        >
                            {{ prod.name }} {{ prod.sku ? `(${prod.sku})` : '' }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- Filter User -->
                <Select v-model="filterState.user_id">
                    <SelectTrigger class="w-full h-9 text-sm truncate">
                        <SelectValue placeholder="Pilih Pengguna" />
                    </SelectTrigger>
                    <SelectContent class="max-h-60">
                        <SelectItem value="all">Semua Pengguna</SelectItem>
                        <SelectItem
                            v-for="usr in users"
                            :key="usr.id"
                            :value="String(usr.id)"
                        >
                            {{ usr.name }} ({{ usr.email }})
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- DataTable Container -->
        <div class="rounded-xl border bg-card shadow-xs">
            <!-- Table Header Controls -->
            <div class="flex items-center justify-between border-b p-4">
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <span>Tampilkan</span>
                    <Select v-model="filterState.per_page">
                        <SelectTrigger class="h-8 w-[72px] text-xs">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="opt in perPageOptions" :key="opt" :value="opt">
                                {{ opt }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <span>data per halaman</span>
                </div>

                <div class="flex items-center gap-3 text-xs text-muted-foreground">
                    <span v-if="hasRunningJobs" class="flex items-center gap-1.5 text-blue-600 dark:text-blue-400 font-medium">
                        <Loader2 class="h-3.5 w-3.5 animate-spin" />
                        Auto-sync setiap 3 detik
                    </span>
                    <span>Total: <strong class="text-foreground">{{ tryOns.total }}</strong> hasil</span>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/50 text-xs font-medium uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 w-20 text-center">Hasil Media</th>
                            <th class="px-4 py-3">Status & Progress</th>
                            <th class="px-4 py-3">Produk Batik</th>
                            <th class="px-4 py-3">Model Mannequin</th>
                            <th class="px-4 py-3">Gender</th>
                            <th class="px-4 py-3">Sumber / Pelanggan</th>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="tryOns.data.length === 0">
                            <td colspan="8" class="py-12 text-center text-muted-foreground">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <Sparkles class="h-8 w-8 text-muted-foreground/50" />
                                    <p class="font-medium">Tidak ada data virtual try-on ditemukan</p>
                                    <p class="text-xs">Klik "Tambah Model Default" untuk men-generate model default pada produk.</p>
                                </div>
                            </td>
                        </tr>

                        <tr
                            v-for="item in tryOns.data"
                            :key="item.id"
                            class="hover:bg-muted/40 transition-colors"
                        >
                            <!-- Thumbnail Hasil Try-On / Processing Icon -->
                            <td class="px-4 py-3 text-center">
                                <div class="relative mx-auto h-14 w-14 overflow-hidden rounded-md border bg-muted flex items-center justify-center group">
                                    <!-- Jika Selesai -->
                                    <template v-if="item.status === 'completed'">
                                        <img
                                            v-if="item.photo_url"
                                            :src="item.photo_url"
                                            :alt="item.product?.name || 'Try On'"
                                            class="h-full w-full object-cover object-top transition group-hover:scale-105"
                                        />
                                        <div v-else class="text-muted-foreground">
                                            <ImageIcon class="h-5 w-5" />
                                        </div>

                                        <!-- Video Indicator Badge -->
                                        <div
                                            v-if="item.video_url"
                                            class="absolute bottom-0.5 right-0.5 rounded bg-black/75 px-1 py-0.5 text-[9px] text-white flex items-center gap-0.5"
                                            title="Memiliki video generasi"
                                        >
                                            <Video class="h-2.5 w-2.5 text-rose-400" />
                                        </div>
                                    </template>

                                    <!-- Jika Sedang Diproses -->
                                    <div
                                        v-else-if="item.status === 'processing'"
                                        class="flex flex-col items-center justify-center text-blue-600 dark:text-blue-400 gap-1 p-1"
                                        title="Sedang di-generate oleh AI"
                                    >
                                        <Loader2 class="h-5 w-5 animate-spin" />
                                        <span class="text-[10px] font-bold">{{ item.progress }}%</span>
                                    </div>

                                    <!-- Jika Dalam Antrean -->
                                    <div
                                        v-else-if="item.status === 'queued'"
                                        class="flex flex-col items-center justify-center text-amber-600 dark:text-amber-400 gap-1 p-1"
                                        title="Menunggu antrean"
                                    >
                                        <Clock class="h-5 w-5 animate-pulse" />
                                        <span class="text-[9px]">Antrean</span>
                                    </div>

                                    <!-- Jika Gagal -->
                                    <div
                                        v-else-if="item.status === 'failed'"
                                        class="flex flex-col items-center justify-center text-destructive gap-1 p-1"
                                        title="Generasi gagal"
                                    >
                                        <XCircle class="h-5 w-5" />
                                        <span class="text-[9px] font-semibold">Gagal</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Status & Progress Bar -->
                            <td class="px-4 py-3 min-w-[190px]">
                                <!-- Processing State -->
                                <div v-if="item.status === 'processing'" class="space-y-1.5">
                                    <div class="flex items-center justify-between text-xs font-medium">
                                        <span class="flex items-center gap-1.5 text-blue-600 dark:text-blue-400 font-semibold">
                                            <Loader2 class="h-3 w-3 animate-spin" />
                                            Diproses AI
                                        </span>
                                        <span class="text-blue-700 dark:text-blue-300 font-bold">{{ item.progress }}%</span>
                                    </div>
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-blue-100 dark:bg-blue-950">
                                        <div
                                            class="h-full bg-blue-600 transition-all duration-300"
                                            :style="{ width: `${item.progress}%` }"
                                        ></div>
                                    </div>
                                    <div class="text-[11px] text-muted-foreground truncate max-w-[210px]" :title="item.step_label || ''">
                                        {{ item.step_label || 'Sedang memproses generasi...' }}
                                    </div>
                                </div>

                                <!-- Queued State -->
                                <div v-else-if="item.status === 'queued'" class="space-y-1">
                                    <Badge variant="outline" class="bg-amber-500/10 text-amber-700 border-amber-200 text-xs gap-1">
                                        <Clock class="h-3 w-3" />
                                        Menunggu Antrean
                                    </Badge>
                                    <div class="text-[11px] text-muted-foreground truncate max-w-[200px]">
                                        {{ item.step_label || 'Dalam antrean worker...' }}
                                    </div>
                                </div>

                                <!-- Failed State -->
                                <div v-else-if="item.status === 'failed'" class="space-y-1">
                                    <Badge variant="destructive" class="text-xs gap-1">
                                        <AlertTriangle class="h-3 w-3" />
                                        Generasi Gagal
                                    </Badge>
                                    <div class="text-[11px] text-destructive truncate max-w-[210px]" :title="item.error_message || item.step_label || ''">
                                        {{ item.error_message || item.step_label || 'Error pada Fashn AI' }}
                                    </div>
                                </div>

                                <!-- Completed State -->
                                <div v-else class="space-y-1">
                                    <Badge variant="outline" class="bg-emerald-500/10 text-emerald-700 border-emerald-300 text-xs gap-1">
                                        <CheckCircle2 class="h-3 w-3 text-emerald-600" />
                                        Selesai
                                    </Badge>
                                    <div class="flex items-center gap-1.5 text-[11px] text-muted-foreground">
                                        <span v-if="item.photo" class="text-emerald-600">✓ Foto</span>
                                        <span v-if="item.photo && item.video">•</span>
                                        <span v-if="item.video" class="text-emerald-600">✓ Video</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Informasi Produk -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        v-if="item.product?.primary_image_url"
                                        class="h-10 w-10 shrink-0 overflow-hidden rounded border bg-muted"
                                    >
                                        <img
                                            :src="item.product.primary_image_url"
                                            :alt="item.product.name"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                    <div class="space-y-0.5">
                                        <div class="font-medium text-foreground">
                                            {{ item.product?.name || 'Produk Tidak Ditemukan' }}
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                            <span v-if="item.product?.sku">SKU: {{ item.product.sku }}</span>
                                            <span v-if="item.product">•</span>
                                            <span class="text-primary font-medium">{{ formatPrice(item.product?.price) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Model Mannequin Avatar -->
                            <td class="px-4 py-3">
                                <div v-if="item.virtual_model" class="flex items-center gap-2.5">
                                    <img
                                        v-if="item.virtual_model.photo_url"
                                        :src="item.virtual_model.photo_url"
                                        :alt="item.virtual_model.name"
                                        class="h-8 w-8 rounded-full object-cover object-top border"
                                    />
                                    <div class="space-y-0.5">
                                        <div class="text-xs font-medium">{{ item.virtual_model.name }}</div>
                                        <span class="text-[10px] text-muted-foreground">ID: #{{ item.virtual_model.id }}</span>
                                    </div>
                                </div>
                                <div v-else>
                                    <Badge variant="secondary" class="text-xs font-normal bg-amber-500/10 text-amber-800 border-amber-200 dark:text-amber-300">
                                        Foto Pelanggan
                                    </Badge>
                                </div>
                            </td>

                            <!-- Gender Badge -->
                            <td class="px-4 py-3">
                                <Badge
                                    v-if="item.gender === 'male'"
                                    variant="outline"
                                    class="bg-blue-500/10 text-blue-700 border-blue-200 dark:text-blue-400"
                                >
                                    Pria (Male)
                                </Badge>
                                <Badge
                                    v-else-if="item.gender === 'female'"
                                    variant="outline"
                                    class="bg-rose-500/10 text-rose-700 border-rose-200 dark:text-rose-400"
                                >
                                    Wanita (Female)
                                </Badge>
                                <span v-else class="text-xs text-muted-foreground">-</span>
                            </td>

                            <!-- Pengguna / Sumber (Without User Indicator) -->
                            <td class="px-4 py-3">
                                <div v-if="item.user" class="space-y-0.5">
                                    <div class="flex items-center gap-1.5 text-xs font-medium text-foreground">
                                        <UserIcon class="h-3.5 w-3.5 text-muted-foreground" />
                                        <span>{{ item.user.name }}</span>
                                    </div>
                                    <div class="text-[11px] text-muted-foreground">{{ item.user.email }}</div>
                                </div>
                                <div v-else>
                                    <Badge
                                        variant="outline"
                                        class="bg-emerald-500/10 text-emerald-700 border-emerald-300 dark:text-emerald-400 dark:border-emerald-800 flex items-center gap-1 w-fit"
                                    >
                                        <CheckCircle2 class="h-3 w-3" />
                                        Model Default Produk
                                    </Badge>
                                    <span class="text-[10px] text-muted-foreground block mt-0.5">
                                        (Showcase Etalase)
                                    </span>
                                </div>
                            </td>

                            <!-- Tanggal Dibuat -->
                            <td class="px-4 py-3 text-xs text-muted-foreground">
                                {{ formatDate(item.created_at) }}
                            </td>

                            <!-- Aksi (Retry, Detail & Hapus) -->
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <!-- Retry Button if Failed -->
                                    <Button
                                        v-if="item.status === 'failed' && can('create product virtual try ons')"
                                        variant="outline"
                                        size="icon"
                                        class="h-8 w-8 text-amber-600 hover:text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-950"
                                        @click="handleRetry(item)"
                                        :disabled="retryingId === item.id"
                                        title="Coba Ulang Generasi AI"
                                    >
                                        <Loader2 v-if="retryingId === item.id" class="h-3.5 w-3.5 animate-spin" />
                                        <RotateCcw v-else class="h-3.5 w-3.5" />
                                    </Button>

                                    <!-- Detail Modal -->
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8"
                                        @click="openDetailModal(item)"
                                        title="Lihat Detail Hasil"
                                    >
                                        <Eye class="h-4 w-4 text-primary" />
                                    </Button>

                                    <!-- Delete Button -->
                                    <Button
                                        v-if="can('delete product virtual try ons')"
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 text-destructive hover:bg-destructive/10"
                                        @click="confirmDelete(item)"
                                        title="Hapus Hasil"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="tryOns.total > 0"
                class="flex flex-col gap-4 border-t p-4 sm:flex-row sm:items-center sm:justify-between text-xs text-muted-foreground"
            >
                <div>
                    Menampilkan <span class="font-medium text-foreground">{{ tryOns.from || 0 }}</span> sampai
                    <span class="font-medium text-foreground">{{ tryOns.to || 0 }}</span> dari
                    <span class="font-medium text-foreground">{{ tryOns.total }}</span> hasil
                </div>

                <div class="flex items-center gap-1">
                    <Button
                        v-for="link in tryOns.links"
                        :key="link.label"
                        :disabled="!link.url"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        class="h-8 min-w-8 px-2 text-xs"
                        @click="link.url && router.get(link.url, {}, { preserveState: true })"
                    >
                        <span v-if="link.label.includes('Previous')">
                            <ChevronLeft class="h-3.5 w-3.5" />
                        </span>
                        <span v-else-if="link.label.includes('Next')">
                            <ChevronRight class="h-3.5 w-3.5" />
                        </span>
                        <span v-else>{{ link.label }}</span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Create Dialog: Tambah Model Default Otomatis via Fashn AI (tryon-max & image-to-video) -->
        <Dialog v-model:open="isCreateDialogOpen">
            <DialogContent class="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Sparkles class="h-5 w-5 text-primary" />
                        Generate Model Default Virtual Try-On
                    </DialogTitle>
                    <DialogDescription>
                        Sistem akan menjalankan antrean AI (tryon-max & image-to-video) untuk memadukan busana batik produk dengan model avatar secara otomatis. File foto dan video akan disimpan ke storage lokal.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitCreate" class="space-y-4 py-2">
                    <!-- Pilih Produk -->
                    <div class="space-y-2">
                        <Label for="create-product">Pilih Produk Batik <span class="text-destructive">*</span></Label>
                        <Select v-model="createForm.product_id">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="-- Pilih Produk --" />
                            </SelectTrigger>
                            <SelectContent class="max-h-60">
                                <SelectItem
                                    v-for="prod in products"
                                    :key="prod.id"
                                    :value="String(prod.id)"
                                >
                                    {{ prod.name }} {{ prod.sku ? `(${prod.sku})` : '' }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="createForm.errors.product_id" class="text-xs text-destructive">
                            {{ createForm.errors.product_id }}
                        </p>
                    </div>

                    <!-- Kuota Status Card untuk Produk Terpilih -->
                    <div v-if="selectedProduct" class="rounded-lg border p-3 bg-muted/40 space-y-2">
                        <div class="flex items-center justify-between text-xs font-medium">
                            <span class="text-muted-foreground">Status Model Default Produk:</span>
                            <span class="font-semibold text-foreground">{{ selectedProduct.name }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div
                                class="flex items-center gap-2 p-2 rounded border"
                                :class="selectedProduct.has_default_male ? 'bg-blue-500/10 border-blue-200 text-blue-800 dark:text-blue-300' : 'bg-background border-dashed text-muted-foreground'"
                            >
                                <component
                                    :is="selectedProduct.has_default_male ? CheckCircle2 : XCircle"
                                    class="h-4 w-4 shrink-0"
                                    :class="selectedProduct.has_default_male ? 'text-blue-600' : 'text-muted-foreground/60'"
                                />
                                <div>
                                    <div class="font-medium">Model Pria (Male)</div>
                                    <div class="text-[11px]">{{ selectedProduct.has_default_male ? 'Sudah Ada (Penuh)' : 'Tersedia' }}</div>
                                </div>
                            </div>

                            <div
                                class="flex items-center gap-2 p-2 rounded border"
                                :class="selectedProduct.has_default_female ? 'bg-rose-500/10 border-rose-200 text-rose-800 dark:text-rose-300' : 'bg-background border-dashed text-muted-foreground'"
                            >
                                <component
                                    :is="selectedProduct.has_default_female ? CheckCircle2 : XCircle"
                                    class="h-4 w-4 shrink-0"
                                    :class="selectedProduct.has_default_female ? 'text-rose-600' : 'text-muted-foreground/60'"
                                />
                                <div>
                                    <div class="font-medium">Model Wanita (Female)</div>
                                    <div class="text-[11px]">{{ selectedProduct.has_default_female ? 'Sudah Ada (Penuh)' : 'Tersedia' }}</div>
                                </div>
                            </div>
                        </div>

                        <div v-if="isAllQuotaFull" class="flex items-center gap-2 text-xs text-amber-700 dark:text-amber-400 bg-amber-500/10 p-2 rounded border border-amber-200">
                            <AlertTriangle class="h-4 w-4 shrink-0" />
                            <span>Produk ini sudah memiliki 1 model pria dan 1 model wanita. Hapus salah satu terlebih dahulu jika ingin menggantinya.</span>
                        </div>
                    </div>

                    <!-- Pilih Gender -->
                    <div class="space-y-2">
                        <Label>Pilih Gender Model Default <span class="text-destructive">*</span></Label>
                        <Select v-model="createForm.gender" :disabled="!selectedProduct || isAllQuotaFull">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="-- Pilih Gender --" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    value="male"
                                    :disabled="!isMaleAvailable"
                                >
                                    Pria (Male) {{ !isMaleAvailable ? '(Sudah Ada)' : '' }}
                                </SelectItem>
                                <SelectItem
                                    value="female"
                                    :disabled="!isFemaleAvailable"
                                >
                                    Wanita (Female) {{ !isFemaleAvailable ? '(Sudah Ada)' : '' }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="createForm.errors.gender" class="text-xs text-destructive">
                            {{ createForm.errors.gender }}
                        </p>
                    </div>

                    <!-- Pilih Model Mannequin Avatar -->
                    <div class="space-y-2">
                        <Label>Pilih Model Virtual / Avatar Sumber Wajah <span class="text-destructive">*</span></Label>
                        <Select
                            v-model="createForm.virtual_try_on_model_id"
                            :disabled="!createForm.gender"
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="-- Pilih Model Virtual --" />
                            </SelectTrigger>
                            <SelectContent class="max-h-60">
                                <SelectItem
                                    v-for="model in filteredModels"
                                    :key="model.id"
                                    :value="String(model.id)"
                                >
                                    {{ model.name }} ({{ model.gender }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="createForm.errors.virtual_try_on_model_id" class="text-xs text-destructive">
                            {{ createForm.errors.virtual_try_on_model_id }}
                        </p>

                        <!-- Preview Model Mannequin Terpilih -->
                        <div v-if="selectedModelInfo" class="flex items-center gap-3 p-2.5 rounded-lg border bg-muted/20">
                            <img
                                v-if="selectedModelInfo.photo_url"
                                :src="selectedModelInfo.photo_url"
                                :alt="selectedModelInfo.name"
                                class="h-11 w-11 rounded-full object-cover border"
                            />
                            <div class="text-xs">
                                <div class="font-medium text-foreground">{{ selectedModelInfo.name }}</div>
                                <div class="text-muted-foreground capitalize">Avatar Sumber: Gender {{ selectedModelInfo.gender }}</div>
                                <div class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5">
                                    Foto model ini akan dipadukan dengan foto produk batik menggunakan API tryon-max Fashn AI.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Toggle Generate Video (image-to-video 480p) -->
                    <div class="rounded-lg border p-3.5 space-y-2 bg-muted/30">
                        <div class="flex items-center justify-between">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2 text-xs font-semibold">
                                    <Video class="h-4 w-4 text-primary" />
                                    <span>Sertakan Generasi Video Model (image-to-video)</span>
                                </div>
                                <p class="text-[11px] text-muted-foreground">
                                    Menghasilkan video simulasi gerakan model sesuai konfigurasi resolusi dan durasi Fashn AI.
                                </p>
                            </div>
                            <Switch v-model="createForm.generate_video" />
                        </div>
                    </div>

                    <DialogFooter class="pt-4 border-t">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isCreateDialogOpen = false"
                            :disabled="createForm.processing"
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            :disabled="createForm.processing || isAllQuotaFull || !createForm.product_id || !createForm.gender || !createForm.virtual_try_on_model_id"
                        >
                            <Loader2 v-if="createForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            <Sparkles v-else class="mr-2 h-4 w-4" />
                            Mulai Antrean Generasi AI
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Detail Modal (Visual, Status & Info) -->
        <Dialog v-model:open="isDetailOpen">
            <DialogContent class="sm:max-w-3xl p-0 overflow-hidden">
                <DialogHeader class="p-6 pb-2 border-b">
                    <div class="flex items-center justify-between">
                        <div>
                            <DialogTitle class="text-lg flex items-center gap-2">
                                <Sparkles class="h-5 w-5 text-primary" />
                                Detail Hasil Virtual Try-On #{{ activeDetail?.id }}
                            </DialogTitle>
                            <DialogDescription>
                                Dibuat pada {{ formatDate(activeDetail?.created_at) }}
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 max-h-[80vh] overflow-y-auto">
                    <!-- Left Column: Visual Media (Photo / Video tabs) -->
                    <div class="space-y-3">
                        <!-- Progress banner inside modal if running -->
                        <div
                            v-if="activeDetail?.status === 'processing' || activeDetail?.status === 'queued'"
                            class="rounded-lg border border-blue-200 bg-blue-50/70 p-3 dark:border-blue-900 dark:bg-blue-950/40 space-y-1.5"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-blue-900 dark:text-blue-200 flex items-center gap-1.5">
                                    <Loader2 class="h-3.5 w-3.5 animate-spin" />
                                    {{ activeDetail.status === 'queued' ? 'Menunggu Antrean Worker' : 'Sedang Diproses Fashn AI' }}
                                </span>
                                <span class="font-bold text-blue-700 dark:text-blue-300">{{ activeDetail.progress }}%</span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-blue-200 dark:bg-blue-900">
                                <div
                                    class="h-full bg-blue-600 transition-all duration-300"
                                    :style="{ width: `${activeDetail.progress}%` }"
                                ></div>
                            </div>
                            <p class="text-[11px] text-blue-700 dark:text-blue-300">
                                {{ activeDetail.step_label }}
                            </p>
                        </div>

                        <!-- Error banner if failed -->
                        <div
                            v-else-if="activeDetail?.status === 'failed'"
                            class="rounded-lg border border-red-200 bg-red-50/70 p-3 dark:border-red-900 dark:bg-red-950/40 space-y-2"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-red-700 dark:text-red-300 flex items-center gap-1.5">
                                    <AlertTriangle class="h-3.5 w-3.5" />
                                    Generasi Gagal
                                </span>
                                <Button
                                    v-if="can('create product virtual try ons')"
                                    variant="outline"
                                    size="sm"
                                    class="h-7 text-xs border-red-300 hover:bg-red-100"
                                    @click="handleRetry(activeDetail)"
                                    :disabled="retryingId === activeDetail.id"
                                >
                                    <Loader2 v-if="retryingId === activeDetail.id" class="mr-1 h-3 w-3 animate-spin" />
                                    <RotateCcw v-else class="mr-1 h-3 w-3" />
                                    Coba Lagi
                                </Button>
                            </div>
                            <p class="text-[11px] text-red-600 dark:text-red-400">
                                {{ activeDetail.error_message || activeDetail.step_label }}
                            </p>
                        </div>

                        <!-- Media Tabs if video available -->
                        <div v-if="activeDetail?.video_url" class="flex gap-2 border-b pb-2">
                            <Button
                                size="sm"
                                :variant="activeMediaTab === 'photo' ? 'default' : 'outline'"
                                @click="activeMediaTab = 'photo'"
                                class="h-8 text-xs"
                            >
                                <ImageIcon class="mr-1.5 h-3.5 w-3.5" />
                                Foto Hasil
                            </Button>
                            <Button
                                size="sm"
                                :variant="activeMediaTab === 'video' ? 'default' : 'outline'"
                                @click="activeMediaTab = 'video'"
                                class="h-8 text-xs"
                            >
                                <Video class="mr-1.5 h-3.5 w-3.5" />
                                Video Hasil
                            </Button>
                        </div>

                        <!-- Photo Display -->
                        <div
                            v-if="activeMediaTab === 'photo' || !activeDetail?.video_url"
                            class="relative overflow-hidden rounded-xl border bg-black/5 flex items-center justify-center min-h-[300px]"
                        >
                            <img
                                v-if="activeDetail?.photo_url"
                                :src="activeDetail.photo_url"
                                :alt="activeDetail.product?.name || 'Hasil Try On'"
                                class="max-h-[420px] w-full object-contain rounded-lg"
                            />
                            <div v-else class="text-center p-6 text-muted-foreground">
                                <ImageIcon class="mx-auto h-12 w-12 mb-2 opacity-50" />
                                <span class="text-xs">Foto belum tersedia / sedang diproses di antrean</span>
                            </div>
                        </div>

                        <!-- Video Display -->
                        <div
                            v-else-if="activeMediaTab === 'video' && activeDetail?.video_url"
                            class="relative overflow-hidden rounded-xl border bg-black flex items-center justify-center min-h-[300px]"
                        >
                            <video
                                :src="activeDetail.video_url"
                                controls
                                autoplay
                                loop
                                class="max-h-[420px] w-full object-contain"
                            ></video>
                        </div>

                        <!-- Direct link buttons -->
                        <div class="flex gap-2">
                            <Button
                                v-if="activeDetail?.photo_url"
                                variant="outline"
                                size="sm"
                                class="w-full text-xs"
                                as-child
                            >
                                <a :href="activeDetail.photo_url" target="_blank" rel="noopener noreferrer">
                                    <ExternalLink class="mr-1.5 h-3.5 w-3.5" />
                                    Buka Foto Asli
                                </a>
                            </Button>
                            <Button
                                v-if="activeDetail?.video_url"
                                variant="outline"
                                size="sm"
                                class="w-full text-xs"
                                as-child
                            >
                                <a :href="activeDetail.video_url" target="_blank" rel="noopener noreferrer">
                                    <ExternalLink class="mr-1.5 h-3.5 w-3.5" />
                                    Buka Video Asli
                                </a>
                            </Button>
                        </div>
                    </div>

                    <!-- Right Column: Information Cards -->
                    <div class="space-y-4">
                        <!-- Sumber Badge & Gender -->
                        <div class="rounded-lg border p-3 bg-muted/30 space-y-1.5">
                            <div class="text-xs font-medium text-muted-foreground">Status Sumber & Gender</div>
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge v-if="activeDetail?.is_default" class="bg-emerald-500/10 text-emerald-700 border-emerald-300">
                                    Model Default Produk (Tanpa User)
                                </Badge>
                                <Badge v-else class="bg-blue-500/10 text-blue-700 border-blue-300">
                                    Dicoba oleh Pelanggan
                                </Badge>

                                <Badge
                                    v-if="activeDetail?.gender === 'male'"
                                    variant="outline"
                                    class="bg-blue-500/10 text-blue-700 border-blue-200"
                                >
                                    Pria (Male)
                                </Badge>
                                <Badge
                                    v-else-if="activeDetail?.gender === 'female'"
                                    variant="outline"
                                    class="bg-rose-500/10 text-rose-700 border-rose-200"
                                >
                                    Wanita (Female)
                                </Badge>
                            </div>
                        </div>

                        <!-- Product Card -->
                        <div class="rounded-lg border p-4 space-y-2">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase text-muted-foreground">
                                <Shirt class="h-3.5 w-3.5" />
                                Informasi Produk
                            </div>
                            <div class="flex items-center gap-3 pt-1">
                                <img
                                    v-if="activeDetail?.product?.primary_image_url"
                                    :src="activeDetail.product.primary_image_url"
                                    :alt="activeDetail.product.name"
                                    class="h-12 w-12 rounded object-cover border"
                                />
                                <div>
                                    <div class="font-medium text-sm text-foreground">
                                        {{ activeDetail?.product?.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        SKU: {{ activeDetail?.product?.sku || '-' }} •
                                        <span class="text-primary font-medium">{{ formatPrice(activeDetail?.product?.price) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Model Mannequin Info -->
                        <div class="rounded-lg border p-4 space-y-2">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase text-muted-foreground">
                                <Sparkles class="h-3.5 w-3.5" />
                                Model / Avatar Wajah Yang Digunakan
                            </div>
                            <div v-if="activeDetail?.virtual_model" class="flex items-center gap-3 pt-1">
                                <img
                                    v-if="activeDetail.virtual_model.photo_url"
                                    :src="activeDetail.virtual_model.photo_url"
                                    :alt="activeDetail.virtual_model.name"
                                    class="h-12 w-12 rounded-full object-cover object-top border"
                                />
                                <div>
                                    <div class="font-medium text-sm">{{ activeDetail.virtual_model.name }}</div>
                                    <div class="text-xs text-muted-foreground capitalize">
                                        Gender Avatar: {{ activeDetail.virtual_model.gender }}
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-xs text-muted-foreground pt-1">
                                Pengguna mengunggah foto pribadi sendiri untuk proses virtual try-on.
                            </div>
                        </div>

                        <!-- User Info -->
                        <div class="rounded-lg border p-4 space-y-2">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase text-muted-foreground">
                                <UserIcon class="h-3.5 w-3.5" />
                                Data Pelanggan
                            </div>
                            <div v-if="activeDetail?.user" class="pt-1">
                                <div class="font-medium text-sm">{{ activeDetail.user.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ activeDetail.user.email }}</div>
                                <div class="text-xs text-muted-foreground mt-0.5">User ID: #{{ activeDetail.user.id }}</div>
                            </div>
                            <div v-else class="text-xs text-muted-foreground pt-1">
                                Tidak ada akun pengguna yang terikat (Showcase model default produk).
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter class="p-4 border-t bg-muted/20">
                    <Button variant="outline" @click="isDetailOpen = false">
                        Tutup
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="isDeleteDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-destructive">
                        <AlertTriangle class="h-5 w-5" />
                        Konfirmasi Hapus Virtual Try-On
                    </DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus data hasil virtual try-on #{{ itemToDelete?.id }}
                        untuk produk <strong class="text-foreground">"{{ itemToDelete?.product?.name }}"</strong>?
                        Tindakan ini akan menghapus file gambar dan rekaman secara permanen.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="isDeleteDialogOpen = false">
                        Batal
                    </Button>
                    <Button variant="destructive" @click="executeDelete">
                        Ya, Hapus Data
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
