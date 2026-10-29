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
    CheckCircle2,
    Clock,
    ExternalLink,
    Info,
    KeyRound,
    Loader2,
    Monitor,
    Save,
    Shirt,
    SlidersHorizontal,
    Sparkles,
    User,
    Users,
    Video,
    Wand2,
} from 'lucide-vue-next';

interface AppConfigItem {
    id: number;
    key: string;
    value: string | null;
    description: string | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    configs: Record<string, AppConfigItem>;
    isApiConfigured: boolean;
    apiConfig?: {
        base_url: string;
        has_key: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fashn AI', href: '#' },
    { title: 'Pengaturan', href: '/fashn-ai/settings' },
];

const form = useForm({
    is_product_virtual_try_on: (props.configs?.is_product_virtual_try_on?.value ?? '1') === '1',
    is_product_virtual_try_on_photo: (props.configs?.is_product_virtual_try_on_photo?.value ?? '1') === '1',
    is_product_virtual_try_on_video: (props.configs?.is_product_virtual_try_on_video?.value ?? '0') === '1',
    video_duration: Number(props.configs?.fashn_video_duration?.value ?? 5),
    video_resolution: props.configs?.fashn_video_resolution?.value ?? '480p',
    virtual_try_on_daily_limit_per_user: Number(props.configs?.virtual_try_on_daily_limit_per_user?.value ?? 3),
    virtual_try_on_weekly_limit_all_users: Number(props.configs?.virtual_try_on_weekly_limit_all_users?.value ?? 100),
});

const submit = () => {
    form.post('/fashn-ai/settings', {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Pengaturan Virtual Try-On - Fashn AI" />

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
                            Pengaturan Virtual Try-On
                        </h1>
                        <p class="text-xs text-muted-foreground sm:text-sm">
                            Pengaturan fitur foto & video, resolusi, durasi, serta batasan kuota per pengguna.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Badge
                    v-if="isApiConfigured"
                    variant="outline"
                    class="gap-1.5 border-emerald-500/40 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 py-1 px-3 text-xs"
                >
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    API Terhubung
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
                <!-- Section 1: Feature Toggles -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base font-semibold">
                            <Wand2 class="h-5 w-5 text-purple-600" />
                            Fitur & Sakelar Mode Virtual Try-On
                        </CardTitle>
                        <CardDescription>
                            Kontrol ketersediaan fitur simulasi busana pada aplikasi customer.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <!-- Master Switch -->
                        <div class="flex items-center justify-between rounded-lg border p-4 bg-muted/20">
                            <div class="space-y-0.5 pr-4">
                                <Label class="text-sm font-semibold flex items-center gap-1.5">
                                    <Sparkles class="h-4 w-4 text-purple-600" />
                                    Aktifkan Fitur Virtual Try-On Produk
                                </Label>
                                <p class="text-xs text-muted-foreground">
                                    Sakelar utama untuk menampilkan tombol Virtual Try-On di halaman katalog produk busana batik.
                                </p>
                            </div>
                            <Switch
                                id="is_product_virtual_try_on"
                                :checked="form.is_product_virtual_try_on"
                                @update:checked="form.is_product_virtual_try_on = $event"
                                :disabled="form.processing"
                            />
                        </div>

                        <!-- Photo Mode Switch -->
                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <div class="space-y-0.5 pr-4">
                                <Label class="text-sm font-medium flex items-center gap-1.5">
                                    <Camera class="h-4 w-4 text-blue-600" />
                                    Mode Foto (AI Image Try-On)
                                </Label>
                                <p class="text-xs text-muted-foreground">
                                    Menghasilkan foto simulasi busana batik pada mannequin atau avatar pengguna.
                                </p>
                            </div>
                            <Switch
                                id="is_product_virtual_try_on_photo"
                                :checked="form.is_product_virtual_try_on_photo"
                                @update:checked="form.is_product_virtual_try_on_photo = $event"
                                :disabled="form.processing || !form.is_product_virtual_try_on"
                            />
                        </div>

                        <!-- Video Mode Switch -->
                        <div class="flex items-center justify-between rounded-lg border p-4">
                            <div class="space-y-0.5 pr-4">
                                <Label class="text-sm font-medium flex items-center gap-1.5">
                                    <Video class="h-4 w-4 text-emerald-600" />
                                    Mode Video (AI Image to Video Try-On)
                                </Label>
                                <p class="text-xs text-muted-foreground">
                                    Menghasilkan video simulasi gerakan dinamis model memakai busana batik.
                                </p>
                            </div>
                            <Switch
                                id="is_product_virtual_try_on_video"
                                :checked="form.is_product_virtual_try_on_video"
                                @update:checked="form.is_product_virtual_try_on_video = $event"
                                :disabled="form.processing || !form.is_product_virtual_try_on"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Section 2: Video Configuration (Duration & Resolution) -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base font-semibold">
                            <Video class="h-5 w-5 text-emerald-600" />
                            Konfigurasi Generasi Video AI
                        </CardTitle>
                        <CardDescription>
                            Atur durasi dan resolusi rendering video saat model busana diproses oleh Fashn AI.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 sm:grid-cols-2">
                        <!-- Video Duration -->
                        <div class="space-y-2">
                            <Label for="video_duration" class="text-xs font-semibold flex items-center gap-1.5">
                                <Clock class="h-4 w-4 text-emerald-600" />
                                Durasi Video
                            </Label>
                            <Select
                                :model-value="String(form.video_duration)"
                                @update:model-value="form.video_duration = Number($event)"
                                :disabled="form.processing"
                            >
                                <SelectTrigger id="video_duration" class="w-full">
                                    <SelectValue placeholder="Pilih durasi video" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="5">
                                        5 Detik (Cepat & Hemat Kredit)
                                    </SelectItem>
                                    <SelectItem value="10">
                                        10 Detik (Lebih Lengkap)
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-[11px] text-muted-foreground">
                                Default 5 detik. Durasi 10 detik membutuhkan waktu rendering dan kredit API yang lebih besar.
                            </p>
                        </div>

                        <!-- Video Resolution -->
                        <div class="space-y-2">
                            <Label for="video_resolution" class="text-xs font-semibold flex items-center gap-1.5">
                                <Monitor class="h-4 w-4 text-blue-600" />
                                Resolusi Video
                            </Label>
                            <Select
                                :model-value="form.video_resolution"
                                @update:model-value="form.video_resolution = String($event)"
                                :disabled="form.processing"
                            >
                                <SelectTrigger id="video_resolution" class="w-full">
                                    <SelectValue placeholder="Pilih resolusi video" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="480p">
                                        480p (Standar - Default)
                                    </SelectItem>
                                    <SelectItem value="720p">
                                        720p (High Definition)
                                    </SelectItem>
                                    <SelectItem value="1080p">
                                        1080p (Full HD - Tajam)
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-[11px] text-muted-foreground">
                                Default 480p untuk efisiensi loading mobile. Resolusi disimpan di cache file agar respons cepat.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Section 3: Quota Limits -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base font-semibold">
                            <Users class="h-5 w-5 text-amber-600" />
                            Batasan Kuota Penggunaan
                        </CardTitle>
                        <CardDescription>
                            Batasi frekuensi penggunaan untuk mengontrol biaya kredit API Fashn AI.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 sm:grid-cols-2">
                        <!-- Daily Limit per User -->
                        <div class="space-y-2">
                            <Label for="virtual_try_on_daily_limit_per_user" class="text-xs font-semibold flex items-center gap-1.5">
                                <User class="h-4 w-4 text-muted-foreground" />
                                Batas Harian per Pengguna
                            </Label>
                            <div class="relative">
                                <Input
                                    id="virtual_try_on_daily_limit_per_user"
                                    v-model.number="form.virtual_try_on_daily_limit_per_user"
                                    type="number"
                                    min="0"
                                    step="1"
                                    placeholder="3"
                                    :disabled="form.processing"
                                />
                            </div>
                            <p class="text-[11px] text-muted-foreground">
                                Maksimal percobaan try-on per akun per hari (default: 3). Isi 0 untuk tidak membatasi.
                            </p>
                        </div>

                        <!-- Weekly Limit All Users -->
                        <div class="space-y-2">
                            <Label for="virtual_try_on_weekly_limit_all_users" class="text-xs font-semibold flex items-center gap-1.5">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                Batas Mingguan Seluruh Pengguna
                            </Label>
                            <div class="relative">
                                <Input
                                    id="virtual_try_on_weekly_limit_all_users"
                                    v-model.number="form.virtual_try_on_weekly_limit_all_users"
                                    type="number"
                                    min="0"
                                    step="1"
                                    placeholder="100"
                                    :disabled="form.processing"
                                />
                            </div>
                            <p class="text-[11px] text-muted-foreground">
                                Kuota total try-on sistem per minggu (default: 100). Mencegah lonjakan biaya tak terduga.
                            </p>
                        </div>
                    </CardContent>

                    <CardFooter class="flex items-center justify-between border-t pt-4">
                        <span class="text-xs text-muted-foreground">
                            Perubahan konfigurasi akan otomatis memperbarui cache aplikasi.
                        </span>
                        <Button type="submit" :disabled="form.processing" class="gap-1.5">
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <Save v-else class="h-4 w-4" />
                            Simpan Pengaturan
                        </Button>
                    </CardFooter>
                </Card>
            </div>

            <!-- Side Info (1 column) -->
            <div class="space-y-6">
                <!-- API Credential Status Card -->
                <Card class="border-purple-200 bg-purple-50/50 dark:border-purple-900/40 dark:bg-purple-950/20">
                    <CardHeader class="pb-3">
                        <CardTitle class="flex items-center gap-2 text-sm font-semibold text-purple-900 dark:text-purple-300">
                            <KeyRound class="h-4 w-4" />
                            Kredensial API Fashn AI
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-xs text-purple-950/80 dark:text-purple-200/80 leading-relaxed">
                        <p>
                            Kunci API dan Base URL endpoint dikelola secara terpisah di menu Konfigurasi aplikasi.
                        </p>
                        <div class="rounded-md border border-purple-200 bg-white/70 p-3 dark:border-purple-900/60 dark:bg-purple-950/50 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-muted-foreground">Status API:</span>
                                <span v-if="isApiConfigured" class="font-semibold text-emerald-600 flex items-center gap-1">
                                    <CheckCircle2 class="h-3.5 w-3.5" /> Terhubung
                                </span>
                                <span v-else class="font-semibold text-amber-600">
                                    Belum Diisi
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-muted-foreground">Base URL:</span>
                                <code class="text-[11px] font-mono text-foreground">{{ apiConfig?.base_url ?? 'https://api.fashn.ai' }}</code>
                            </div>
                        </div>

                        <Link
                            href="/configurations/fashn-ai"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-purple-700 hover:text-purple-900 dark:text-purple-300 dark:hover:text-purple-100 underline pt-1"
                        >
                            <KeyRound class="h-3.5 w-3.5" />
                            Ubah API Key & Base URL di Konfigurasi
                            <ExternalLink class="h-3 w-3" />
                        </Link>
                    </CardContent>
                </Card>

                <!-- Navigation Quick Links -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-sm font-semibold">Tautan Terkait</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2 text-xs">
                        <Link
                            href="/virtual-try-on-models"
                            class="flex items-center justify-between rounded-md border p-2.5 transition-colors hover:bg-muted"
                        >
                            <span class="flex items-center gap-2 font-medium">
                                <Users class="h-4 w-4 text-purple-600" />
                                Model Mannequin Avatar
                            </span>
                            <ExternalLink class="h-3.5 w-3.5 text-muted-foreground" />
                        </Link>
                        <Link
                            href="/product-virtual-try-ons"
                            class="flex items-center justify-between rounded-md border p-2.5 transition-colors hover:bg-muted"
                        >
                            <span class="flex items-center gap-2 font-medium">
                                <Shirt class="h-4 w-4 text-primary" />
                                Katalog Product Virtual Try-On
                            </span>
                            <ExternalLink class="h-3.5 w-3.5 text-muted-foreground" />
                        </Link>
                        <Link
                            href="/configurations/fashn-ai"
                            class="flex items-center justify-between rounded-md border p-2.5 transition-colors hover:bg-muted"
                        >
                            <span class="flex items-center gap-2 font-medium">
                                <KeyRound class="h-4 w-4 text-purple-600" />
                                Kredensial API Fashn AI
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

