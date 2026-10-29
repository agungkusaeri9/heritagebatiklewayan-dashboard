<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Calendar,
    Camera,
    Check,
    Clock,
    Copy,
    ExternalLink,
    Eye,
    EyeOff,
    Info,
    KeyRound,
    Loader2,
    Monitor,
    Save,
    SlidersHorizontal,
    Sparkles,
    User,
    Users,
    Video,
    Wand2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface AppConfigItem {
    id: number;
    key: string;
    value: string | null;
    description: string | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    config: AppConfigItem;
    configs?: Record<string, AppConfigItem>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Konfigurasi', href: '#' },
    { title: 'Fashn AI', href: '/configurations/fashn-ai' },
];

const showKey = ref(false);
const isCopied = ref(false);

const form = useForm({
    api_key: props.configs?.fashn_api_key?.value ?? props.config.value ?? '',
    base_url: props.configs?.fashn_api_base_url?.value ?? 'https://api.fashn.ai',
    is_product_virtual_try_on: (props.configs?.is_product_virtual_try_on?.value ?? '1') === '1',
    is_product_virtual_try_on_photo: (props.configs?.is_product_virtual_try_on_photo?.value ?? '1') === '1',
    is_product_virtual_try_on_video: (props.configs?.is_product_virtual_try_on_video?.value ?? '0') === '1',
    video_duration: Number(props.configs?.fashn_video_duration?.value ?? 5),
    video_resolution: props.configs?.fashn_video_resolution?.value ?? '480p',
    virtual_try_on_daily_limit_per_user: Number(props.configs?.virtual_try_on_daily_limit_per_user?.value ?? 3),
    virtual_try_on_weekly_limit_all_users: Number(props.configs?.virtual_try_on_weekly_limit_all_users?.value ?? 100),
    description: props.configs?.fashn_api_key?.description ?? props.config.description ?? 'API Key untuk layanan integrasi Fashn AI',
});

const isConfigured = computed(() => !!form.api_key && form.api_key.trim().length > 0);

const submit = () => {
    form.post('/configurations/fashn-ai', {
        preserveScroll: true,
    });
};

const copyKey = async (text: string) => {
    if (!text) return;
    try {
        await navigator.clipboard.writeText(text);
        isCopied.value = true;
        setTimeout(() => {
            isCopied.value = false;
        }, 2000);
    } catch {
        // fallback
    }
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Konfigurasi Fashn AI & Virtual Try-On" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300">
                        <Sparkles class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                            Konfigurasi Fashn AI
                        </h1>
                        <p class="text-xs text-muted-foreground sm:text-sm">
                            Pengaturan API Key, fitur Virtual Try-On (Foto & Video), serta batasan kuota per pengguna.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Badge
                    v-if="isConfigured"
                    variant="outline"
                    class="gap-1.5 border-emerald-500/40 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 py-1 px-3 text-xs"
                >
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    API Terkonfigurasi
                </Badge>
                <Badge
                    v-else
                    variant="outline"
                    class="gap-1.5 border-amber-500/40 bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 py-1 px-3 text-xs"
                >
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    API Belum Dikonfigurasi
                </Badge>
            </div>
        </div>

        <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-3">
            <!-- Main Settings (2 columns) -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Section 1: API Key Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base font-semibold">
                            <KeyRound class="h-5 w-5 text-primary" />
                            Kredensial API Fashn AI
                        </CardTitle>
                        <CardDescription>
                            API Key digunakan untuk autentikasi koneksi ke server Fashn.ai.
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <div class="space-y-1.5">
                            <Label class="text-xs text-muted-foreground">Parameter Key</Label>
                            <div class="flex items-center gap-2">
                                <code class="rounded bg-muted px-2.5 py-1 font-mono text-xs font-semibold text-foreground">
                                    fashn_api_key
                                </code>
                            </div>
                        </div>

                        <!-- Base URL Input -->
                        <div class="space-y-1.5">
                            <Label for="base_url" class="text-sm font-medium">
                                Base URL Fashn AI
                            </Label>
                            <Input
                                id="base_url"
                                v-model="form.base_url"
                                type="url"
                                placeholder="https://api.fashn.ai"
                                class="font-mono text-sm"
                                :disabled="form.processing"
                            />
                            <p v-if="form.errors.base_url" class="text-xs text-destructive">
                                {{ form.errors.base_url }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Default: <code>https://api.fashn.ai</code>. Digunakan untuk request endpoint <code>/v1/run</code> dan <code>/v1/status</code>.
                            </p>
                        </div>

                        <!-- API Key Input -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <Label for="api_key" class="text-sm font-medium">
                                    API Key Fashn AI
                                </Label>
                                <button
                                    v-if="form.api_key"
                                    type="button"
                                    @click="copyKey(form.api_key)"
                                    class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors"
                                >
                                    <Check v-if="isCopied" class="h-3 w-3 text-green-600" />
                                    <Copy v-else class="h-3 w-3" />
                                    <span>{{ isCopied ? 'Tersalin' : 'Salin Key' }}</span>
                                </button>
                            </div>

                            <div class="relative">
                                <Input
                                    id="api_key"
                                    v-model="form.api_key"
                                    :type="showKey ? 'text' : 'password'"
                                    placeholder="Masukkan API Key Fashn AI (misal: fa-xxxxxxxxxxxx)..."
                                    class="pr-10 font-mono text-sm"
                                    :disabled="form.processing"
                                />
                                <button
                                    type="button"
                                    @click="showKey = !showKey"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                                    :title="showKey ? 'Sembunyikan' : 'Tampilkan'"
                                >
                                    <EyeOff v-if="showKey" class="h-4 w-4" />
                                    <Eye v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="form.errors.api_key" class="text-xs text-destructive">
                                {{ form.errors.api_key }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Dapatkan API Key di <a href="https://fashn.ai" target="_blank" class="underline hover:text-primary">fashn.ai</a>.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Section 2: Feature Toggles (Try-On Modes) -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base font-semibold">
                            <Wand2 class="h-5 w-5 text-purple-600" />
                            Fitur & Mode Virtual Try-On
                        </CardTitle>
                        <CardDescription>
                            Atur ketersediaan fitur fitting produk dan mode yang diizinkan untuk pelanggan.
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="divide-y divide-border">
                        <!-- Master Switch: is_product_virtual_try_on -->
                        <div class="flex items-center justify-between pb-4">
                            <div class="space-y-0.5">
                                <Label class="text-sm font-semibold flex items-center gap-2">
                                    <span>Aktifkan Virtual Try-On Produk</span>
                                    <code class="text-[11px] font-mono px-1.5 py-0.5 rounded bg-muted text-muted-foreground">
                                        is_product_virtual_try_on
                                    </code>
                                </Label>
                                <p class="text-xs text-muted-foreground">
                                    Tombol coba pakaian (Try-On) akan muncul di halaman katalog produk e-commerce.
                                </p>
                            </div>
                            <Switch
                                v-model="form.is_product_virtual_try_on"
                                :disabled="form.processing"
                            />
                        </div>

                        <!-- Photo Mode: is_product_virtual_try_on_photo -->
                        <div class="flex items-center justify-between py-4">
                            <div class="space-y-0.5">
                                <Label class="text-sm font-medium flex items-center gap-2">
                                    <Camera class="h-4 w-4 text-primary" />
                                    <span>Mode Foto (Image Try-On)</span>
                                    <code class="text-[11px] font-mono px-1.5 py-0.5 rounded bg-muted text-muted-foreground">
                                        is_product_virtual_try_on_photo
                                    </code>
                                </Label>
                                <p class="text-xs text-muted-foreground">
                                    Pengguna dapat mencoba pakaian batik ke foto model 2D statis.
                                </p>
                            </div>
                            <Switch
                                v-model="form.is_product_virtual_try_on_photo"
                                :disabled="form.processing || !form.is_product_virtual_try_on"
                            />
                        </div>

                        <!-- Video Mode: is_product_virtual_try_on_video -->
                        <div class="flex items-center justify-between pt-4">
                            <div class="space-y-0.5">
                                <Label class="text-sm font-medium flex items-center gap-2">
                                    <Video class="h-4 w-4 text-purple-600" />
                                    <span>Mode Video (Video Try-On)</span>
                                    <code class="text-[11px] font-mono px-1.5 py-0.5 rounded bg-muted text-muted-foreground">
                                        is_product_virtual_try_on_video
                                    </code>
                                </Label>
                                <p class="text-xs text-muted-foreground">
                                    Pengguna dapat mencoba pakaian batik ke video model bergerak (membutuhkan komputasi AI lebih tinggi).
                                </p>
                            </div>
                            <Switch
                                v-model="form.is_product_virtual_try_on_video"
                                :disabled="form.processing || !form.is_product_virtual_try_on"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Section: Video Parameters (Duration & Resolution) -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base font-semibold">
                            <SlidersHorizontal class="h-5 w-5 text-purple-600" />
                            Format & Kualitas Video (image-to-video)
                        </CardTitle>
                        <CardDescription>
                            Atur durasi video dan resolusi default untuk hasil rendering simulasi model video. Data ini disimpan dalam cache file sistem.
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="grid gap-4 sm:grid-cols-2">
                        <!-- Video Duration (5s or 10s) -->
                        <div class="space-y-2 rounded-lg border p-4 bg-card">
                            <div class="flex items-center gap-2">
                                <Clock class="h-4 w-4 text-primary" />
                                <Label class="text-xs font-semibold">
                                    Durasi Video Generasi
                                </Label>
                            </div>
                            <Select v-model="form.video_duration">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Pilih durasi video" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="5">5 Detik (Default - Cepat & Hemat Kredit)</SelectItem>
                                    <SelectItem :value="10">10 Detik (Lebih Panjang)</SelectItem>
                                </SelectContent>
                            </Select>
                            <code class="text-[10px] block font-mono text-muted-foreground/80">
                                fashn_video_duration
                            </code>
                            <p class="text-[11px] text-muted-foreground leading-snug">
                                Fashn AI mendukung durasi video 5 detik atau 10 detik per siklus generasi.
                            </p>
                        </div>

                        <!-- Video Resolution (480p, 720p, 1080p) -->
                        <div class="space-y-2 rounded-lg border p-4 bg-card">
                            <div class="flex items-center gap-2">
                                <Monitor class="h-4 w-4 text-primary" />
                                <Label class="text-xs font-semibold">
                                    Resolusi Video Output
                                </Label>
                            </div>
                            <Select v-model="form.video_resolution">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Pilih resolusi video" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="480p">480p (Default - Cepat & Stabil)</SelectItem>
                                    <SelectItem value="720p">720p (HD - Kualitas Menengah)</SelectItem>
                                    <SelectItem value="1080p">1080p (Full HD - Kualitas Tinggi)</SelectItem>
                                </SelectContent>
                            </Select>
                            <code class="text-[10px] block font-mono text-muted-foreground/80">
                                fashn_video_resolution
                            </code>
                            <p class="text-[11px] text-muted-foreground leading-snug">
                                Resolusi 480p direkomendasikan untuk kecepatan proses antrean, sedangkan 1080p menghasilkan video beresolusi tajam.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Section 3: Usage Limits -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base font-semibold">
                            <Users class="h-5 w-5 text-blue-600" />
                            Batasan Kuota Penggunaan (Usage Limits)
                        </CardTitle>
                        <CardDescription>
                            Batasi frekuensi penggunaan untuk mengontrol biaya kredit API Fashn AI.
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="grid gap-4 sm:grid-cols-2">
                        <!-- Daily limit per user -->
                        <div class="space-y-2 rounded-lg border p-4">
                            <div class="flex items-center gap-2">
                                <User class="h-4 w-4 text-muted-foreground" />
                                <Label for="daily_limit" class="text-xs font-semibold">
                                    Batas Per User / Hari
                                </Label>
                            </div>
                            <div class="flex items-center gap-2">
                                <Input
                                    id="daily_limit"
                                    type="number"
                                    min="0"
                                    max="1000"
                                    v-model.number="form.virtual_try_on_daily_limit_per_user"
                                    :disabled="form.processing || !form.is_product_virtual_try_on"
                                    class="w-28 text-sm"
                                />
                                <span class="text-xs text-muted-foreground">kali / hari</span>
                            </div>
                            <code class="text-[10px] block font-mono text-muted-foreground/80">
                                virtual_try_on_daily_limit_per_user
                            </code>
                            <p class="text-[11px] text-muted-foreground leading-snug">
                                Setiap user maksimal mencoba {{ form.virtual_try_on_daily_limit_per_user || 0 }} kali setiap harinya.
                            </p>
                        </div>

                        <!-- Weekly limit all users -->
                        <div class="space-y-2 rounded-lg border p-4">
                            <div class="flex items-center gap-2">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                <Label for="weekly_limit" class="text-xs font-semibold">
                                    Batas Mingguan Seluruh User
                                </Label>
                            </div>
                            <div class="flex items-center gap-2">
                                <Input
                                    id="weekly_limit"
                                    type="number"
                                    min="0"
                                    max="100000"
                                    v-model.number="form.virtual_try_on_weekly_limit_all_users"
                                    :disabled="form.processing || !form.is_product_virtual_try_on"
                                    class="w-28 text-sm"
                                />
                                <span class="text-xs text-muted-foreground">kali / minggu</span>
                            </div>
                            <code class="text-[10px] block font-mono text-muted-foreground/80">
                                virtual_try_on_weekly_limit_all_users
                            </code>
                            <p class="text-[11px] text-muted-foreground leading-snug">
                                Kuota total global seluruh pelanggan maksimal {{ form.virtual_try_on_weekly_limit_all_users || 0 }} kali per minggu.
                            </p>
                        </div>
                    </CardContent>

                    <CardFooter class="flex items-center justify-between border-t pt-4">
                        <span class="text-xs text-muted-foreground">
                            Terakhir diperbarui: {{ new Date(config.updated_at).toLocaleString('id-ID') }}
                        </span>
                        <Button type="submit" :disabled="form.processing" class="gap-1.5">
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <Save v-else class="h-4 w-4" />
                            Simpan Semua Konfigurasi
                        </Button>
                    </CardFooter>
                </Card>
            </div>

            <!-- Side Info Card (1 column) -->
            <div class="space-y-6">
                <!-- Status Summary Card -->
                <Card class="border-purple-200 bg-purple-50/50 dark:border-purple-900/40 dark:bg-purple-950/20">
                    <CardHeader class="pb-3">
                        <CardTitle class="flex items-center gap-2 text-base text-purple-900 dark:text-purple-300">
                            <Info class="h-4 w-4" />
                            Ringkasan Status Fitur
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-xs text-purple-950/80 dark:text-purple-200/80 leading-relaxed">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span>Status Try-On Produk:</span>
                                <Badge :variant="form.is_product_virtual_try_on ? 'default' : 'secondary'">
                                    {{ form.is_product_virtual_try_on ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Try-On Foto:</span>
                                <span class="font-medium" :class="form.is_product_virtual_try_on_photo && form.is_product_virtual_try_on ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'">
                                    {{ form.is_product_virtual_try_on_photo && form.is_product_virtual_try_on ? 'Diizinkan' : 'Terkunci' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Try-On Video:</span>
                                <span class="font-medium" :class="form.is_product_virtual_try_on_video && form.is_product_virtual_try_on ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'">
                                    {{ form.is_product_virtual_try_on_video && form.is_product_virtual_try_on ? 'Diizinkan' : 'Terkunci' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between border-t border-purple-200 dark:border-purple-900 pt-2">
                                <span>Kuota Harian User:</span>
                                <span class="font-semibold">{{ form.virtual_try_on_daily_limit_per_user }}x</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Kuota Global Mingguan:</span>
                                <span class="font-semibold">{{ form.virtual_try_on_weekly_limit_all_users }}x</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Navigation Quick Links -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-semibold">Tautan Terkait</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-xs">
                        <Link
                            href="/products"
                            class="flex items-center justify-between rounded-md border p-2.5 transition-colors hover:bg-muted"
                        >
                            <span class="flex items-center gap-2 font-medium">
                                <Sparkles class="h-4 w-4 text-purple-600" />
                                Lihat Katalog Produk
                            </span>
                            <ExternalLink class="h-3.5 w-3.5 text-muted-foreground" />
                        </Link>
                        <Link
                            href="/configurations"
                            class="flex items-center justify-between rounded-md border p-2.5 transition-colors hover:bg-muted"
                        >
                            <span class="flex items-center gap-2 font-medium">
                                <SlidersHorizontal class="h-4 w-4 text-primary" />
                                Semua Konfigurasi Aplikasi
                            </span>
                            <ExternalLink class="h-3.5 w-3.5 text-muted-foreground" />
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </form>
    </div>
</template>
