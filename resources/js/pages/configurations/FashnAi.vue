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
import { Textarea } from '@/components/ui/textarea';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Check,
    Copy,
    ExternalLink,
    Eye,
    EyeOff,
    Info,
    KeyRound,
    Loader2,
    Save,
    Shirt,
    SlidersHorizontal,
    Sparkles,
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
    baseUrlConfig?: AppConfigItem;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Konfigurasi', href: '#' },
    { title: 'Fashn AI', href: '/dashboard/configurations/fashn-ai' },
];

const showKey = ref(false);
const isCopied = ref(false);

const form = useForm({
    base_url: props.baseUrlConfig?.value ?? 'https://api.fashn.ai',
    api_key: props.config.value ?? '',
    description: props.config.description ?? 'API Key untuk layanan integrasi Fashn AI',
});

const isConfigured = computed(() => !!form.api_key && form.api_key.trim().length > 0);

const submit = () => {
    form.post('/dashboard/configurations/fashn-ai', {
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
        <Head title="Konfigurasi Fashn AI" />

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
                            Pengaturan Kredensial API Key dan Base URL untuk koneksi layanan AI Virtual Try-On.
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

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Form Card (2 columns) -->
            <div class="lg:col-span-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <KeyRound class="h-5 w-5 text-primary" />
                            Kredensial API Fashn AI
                        </CardTitle>
                        <CardDescription>
                            API Key dan Base URL digunakan untuk autentikasi koneksi ke server Fashn.ai (endpoint <code>/v1/run</code> dan <code>/v1/status</code>).
                        </CardDescription>
                    </CardHeader>

                    <form @submit.prevent="submit">
                        <CardContent class="space-y-4">
                            <!-- Parameter Keys -->
                            <div class="space-y-1.5">
                                <Label class="text-xs text-muted-foreground">Parameter Keys</Label>
                                <div class="flex flex-wrap items-center gap-2">
                                    <code class="rounded bg-muted px-2.5 py-1 font-mono text-xs font-semibold text-foreground">
                                        {{ baseUrlConfig?.key ?? 'fashn_api_base_url' }}
                                    </code>
                                    <code class="rounded bg-muted px-2.5 py-1 font-mono text-xs font-semibold text-foreground">
                                        {{ config.key }}
                                    </code>
                                    <span class="text-xs text-muted-foreground">(Disimpan dalam tabel app_configs & di-cache)</span>
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
                                    Default: <code>https://api.fashn.ai</code>. Digunakan untuk request API generasi tryon model dan video.
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
                                    Dapatkan API Key dari dashboard <a href="https://fashn.ai" target="_blank" class="underline hover:text-primary">fashn.ai</a>.
                                </p>
                            </div>

                            <!-- Description Input -->
                            <div class="space-y-1.5">
                                <Label for="description" class="text-sm font-medium">
                                    Catatan / Deskripsi
                                </Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="2"
                                    placeholder="Keterangan mengenai API key ini..."
                                    :disabled="form.processing"
                                    class="text-xs"
                                />
                                <p v-if="form.errors.description" class="text-xs text-destructive">
                                    {{ form.errors.description }}
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
                                Simpan Kredensial
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>

            <!-- Side Info Card (1 column) -->
            <div class="space-y-6">
                <!-- Info Guide -->
                <Card class="border-purple-200 bg-purple-50/50 dark:border-purple-900/40 dark:bg-purple-950/20">
                    <CardHeader class="pb-3">
                        <CardTitle class="flex items-center gap-2 text-base text-purple-900 dark:text-purple-300">
                            <Info class="h-4 w-4" />
                            Pengaturan Fitur & Kuota
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-xs text-purple-950/80 dark:text-purple-200/80 leading-relaxed">
                        <p>
                            Sakelar mode foto/video, konfigurasi resolusi (480p/720p/1080p), durasi video, serta batas kuota harian & mingguan kini dikelola pada menu:
                        </p>
                        <div class="rounded-md border border-purple-200 bg-white/80 p-3 dark:border-purple-900/60 dark:bg-purple-950/50">
                            <Link
                                href="/dashboard/fashn-ai/settings"
                                class="inline-flex items-center gap-1.5 font-semibold text-purple-700 hover:text-purple-900 dark:text-purple-300 dark:hover:text-purple-100"
                            >
                                <Wand2 class="h-4 w-4" />
                                Buka Pengaturan Fitur Try-On
                                <ExternalLink class="h-3 w-3" />
                            </Link>
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
                            href="/dashboard/fashn-ai/settings"
                            class="flex items-center justify-between rounded-md border p-2.5 transition-colors hover:bg-muted"
                        >
                            <span class="flex items-center gap-2 font-medium">
                                <Wand2 class="h-4 w-4 text-purple-600" />
                                Pengaturan Fitur & Kuota Try-On
                            </span>
                            <ExternalLink class="h-3.5 w-3.5 text-muted-foreground" />
                        </Link>
                        <Link
                            href="/dashboard/product-virtual-try-ons"
                            class="flex items-center justify-between rounded-md border p-2.5 transition-colors hover:bg-muted"
                        >
                            <span class="flex items-center gap-2 font-medium">
                                <Shirt class="h-4 w-4 text-primary" />
                                Katalog Product Virtual Try-On
                            </span>
                            <ExternalLink class="h-3.5 w-3.5 text-muted-foreground" />
                        </Link>
                        <Link
                            href="/dashboard/configurations"
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
        </div>
    </div>
</template>
