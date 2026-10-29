<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    CheckCircle2,
    ExternalLink,
    Image as ImageIcon,
    Loader2,
    RotateCcw,
    Shirt,
    Sparkles,
    User as UserIcon,
    Video,
} from 'lucide-vue-next';
import { ref } from 'vue';

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

interface TryOnDetail {
    id: number;
    product: ProductInfo | null;
    virtual_model: VirtualModelInfo | null;
    user: UserInfo | null;
    gender?: 'male' | 'female' | 'unisex' | null;
    photo: string | null;
    photo_url: string | null;
    video: string | null;
    video_url: string | null;
    status: 'queued' | 'processing' | 'completed' | 'failed';
    progress: number;
    step_label: string | null;
    error_message: string | null;
    is_default: boolean;
    created_at: string | null;
}

interface Props {
    tryOn: TryOnDetail;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fashn AI', href: '#' },
    { title: 'Product Virtual Try-On', href: '/product-virtual-try-ons' },
    { title: `Detail #${props.tryOn.id}`, href: '#' },
];

const activeMediaTab = ref<'photo' | 'video'>(props.tryOn.photo_url ? 'photo' : 'video');
const isRetrying = ref(false);

const handleRetry = () => {
    isRetrying.value = true;
    router.post(`/product-virtual-try-ons/${props.tryOn.id}/retry`, {}, {
        onFinish: () => {
            isRetrying.value = false;
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
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 bg-muted/20">
        <Head :title="`Detail Virtual Try-On #${tryOn.id} - Fashn AI`" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Button variant="outline" size="icon" as-child>
                    <Link href="/product-virtual-try-ons">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-xl font-semibold tracking-tight">Detail Virtual Try-On #{{ tryOn.id }}</h1>
                    <p class="text-xs text-muted-foreground">Dibuat pada {{ formatDate(tryOn.created_at) }}</p>
                </div>
            </div>

            <Button
                v-if="tryOn.status === 'failed'"
                variant="outline"
                size="sm"
                @click="handleRetry"
                :disabled="isRetrying"
            >
                <Loader2 v-if="isRetrying" class="mr-2 h-4 w-4 animate-spin" />
                <RotateCcw v-else class="mr-2 h-4 w-4" />
                Coba Lagi (Retry)
            </Button>
        </div>

        <!-- Status & Progress Banner -->
        <div
            v-if="tryOn.status === 'processing' || tryOn.status === 'queued'"
            class="rounded-xl border border-blue-200 bg-blue-50/70 p-4 dark:border-blue-900 dark:bg-blue-950/40 space-y-2"
        >
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Loader2 class="h-4 w-4 text-blue-600 animate-spin" />
                    <span class="text-sm font-semibold text-blue-900 dark:text-blue-200">
                        {{ tryOn.status === 'queued' ? 'Dalam Antrean Antarmuka Fashn AI' : 'Sedang Diproses oleh AI' }}
                    </span>
                </div>
                <span class="text-xs font-bold text-blue-700 dark:text-blue-300">{{ tryOn.progress }}%</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-blue-200 dark:bg-blue-900">
                <div
                    class="h-full bg-blue-600 transition-all duration-300"
                    :style="{ width: `${tryOn.progress}%` }"
                ></div>
            </div>
            <p class="text-xs text-blue-700 dark:text-blue-300">
                {{ tryOn.step_label || 'Sedang memproses generasi...' }}
            </p>
        </div>

        <div
            v-else-if="tryOn.status === 'failed'"
            class="rounded-xl border border-red-200 bg-red-50/70 p-4 dark:border-red-900 dark:bg-red-950/40 space-y-1.5"
        >
            <div class="flex items-center gap-2 text-red-700 dark:text-red-300 font-semibold text-sm">
                <AlertTriangle class="h-4 w-4" />
                <span>Generasi Fashn AI Gagal</span>
            </div>
            <p class="text-xs text-red-600 dark:text-red-400">
                {{ tryOn.error_message || tryOn.step_label || 'Terjadi kesalahan saat memproses model di Fashn AI.' }}
            </p>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Media Visual Column -->
            <div class="space-y-3">
                <div v-if="tryOn.video_url" class="flex gap-2 border-b pb-2">
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

                <div
                    v-if="activeMediaTab === 'photo' || !tryOn.video_url"
                    class="relative overflow-hidden rounded-xl border bg-black/5 flex items-center justify-center min-h-[340px]"
                >
                    <img
                        v-if="tryOn.photo_url"
                        :src="tryOn.photo_url"
                        :alt="tryOn.product?.name || 'Hasil'"
                        class="max-h-[460px] w-full object-contain rounded-lg"
                    />
                    <div v-else class="text-center p-6 text-muted-foreground">
                        <ImageIcon class="mx-auto h-12 w-12 mb-2 opacity-50" />
                        <span>Foto belum tersedia atau sedang di-generate</span>
                    </div>
                </div>

                <div
                    v-else-if="activeMediaTab === 'video' && tryOn.video_url"
                    class="relative overflow-hidden rounded-xl border bg-black flex items-center justify-center min-h-[340px]"
                >
                    <video
                        :src="tryOn.video_url"
                        controls
                        autoplay
                        loop
                        class="max-h-[460px] w-full object-contain"
                    ></video>
                </div>

                <div class="flex gap-2">
                    <Button
                        v-if="tryOn.photo_url"
                        variant="outline"
                        size="sm"
                        class="w-full text-xs"
                        as-child
                    >
                        <a :href="tryOn.photo_url" target="_blank" rel="noopener noreferrer">
                            <ExternalLink class="mr-1.5 h-3.5 w-3.5" />
                            Buka Foto Asli
                        </a>
                    </Button>
                    <Button
                        v-if="tryOn.video_url"
                        variant="outline"
                        size="sm"
                        class="w-full text-xs"
                        as-child
                    >
                        <a :href="tryOn.video_url" target="_blank" rel="noopener noreferrer">
                            <ExternalLink class="mr-1.5 h-3.5 w-3.5" />
                            Buka Video Asli
                        </a>
                    </Button>
                </div>
            </div>

            <!-- Details Information Column -->
            <div class="space-y-4">
                <div class="rounded-lg border p-3 bg-muted/30 space-y-1.5">
                    <div class="text-xs font-medium text-muted-foreground">Status Sumber & Gender</div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge v-if="tryOn.is_default" class="bg-emerald-500/10 text-emerald-700 border-emerald-300">
                            Model Default Produk (Tanpa User)
                        </Badge>
                        <Badge v-else class="bg-blue-500/10 text-blue-700 border-blue-300">
                            Dicoba oleh Pelanggan
                        </Badge>

                        <Badge
                            v-if="tryOn.gender === 'male'"
                            variant="outline"
                            class="bg-blue-500/10 text-blue-700 border-blue-200"
                        >
                            Pria (Male)
                        </Badge>
                        <Badge
                            v-else-if="tryOn.gender === 'female'"
                            variant="outline"
                            class="bg-rose-500/10 text-rose-700 border-rose-200"
                        >
                            Wanita (Female)
                        </Badge>
                    </div>
                </div>

                <div class="rounded-lg border p-4 space-y-2">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase text-muted-foreground">
                        <Shirt class="h-3.5 w-3.5" />
                        Informasi Produk
                    </div>
                    <div class="flex items-center gap-3 pt-1">
                        <img
                            v-if="tryOn.product?.primary_image_url"
                            :src="tryOn.product.primary_image_url"
                            :alt="tryOn.product.name"
                            class="h-12 w-12 rounded object-cover border"
                        />
                        <div>
                            <div class="font-medium text-sm text-foreground">{{ tryOn.product?.name }}</div>
                            <div class="text-xs text-muted-foreground">
                                SKU: {{ tryOn.product?.sku || '-' }} •
                                <span class="text-primary font-medium">{{ formatPrice(tryOn.product?.price) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border p-4 space-y-2">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase text-muted-foreground">
                        <Sparkles class="h-3.5 w-3.5" />
                        Model Avatar / Mannequin
                    </div>
                    <div v-if="tryOn.virtual_model" class="flex items-center gap-3 pt-1">
                        <img
                            v-if="tryOn.virtual_model.photo_url"
                            :src="tryOn.virtual_model.photo_url"
                            :alt="tryOn.virtual_model.name"
                            class="h-12 w-12 rounded-full object-cover object-top border"
                        />
                        <div>
                            <div class="font-medium text-sm">{{ tryOn.virtual_model.name }}</div>
                            <div class="text-xs text-muted-foreground capitalize">Gender Avatar: {{ tryOn.virtual_model.gender }}</div>
                        </div>
                    </div>
                    <div v-else class="text-xs text-muted-foreground pt-1">
                        Pengguna mengunggah foto sendiri untuk try-on.
                    </div>
                </div>

                <div class="rounded-lg border p-4 space-y-2">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase text-muted-foreground">
                        <UserIcon class="h-3.5 w-3.5" />
                        Data Pelanggan
                    </div>
                    <div v-if="tryOn.user" class="pt-1">
                        <div class="font-medium text-sm">{{ tryOn.user.name }}</div>
                        <div class="text-xs text-muted-foreground">{{ tryOn.user.email }}</div>
                    </div>
                    <div v-else class="text-xs text-muted-foreground pt-1">
                        Tidak ada akun pengguna (Merupakan showcase model default produk).
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

