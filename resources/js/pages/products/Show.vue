<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Box, CheckCircle, Clock, Edit, FileText, Image as ImageIcon, Info, Layers, ScanFace, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface Variant {
    type: string;
    values: string[];
}

interface ProductImage {
    id: number;
    url: string;
    sort_order: number;
}

interface Product {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    primary_image_url: string | null;
    price: string;
    stock: number;
    sku: string | null;
    weight: number;
    is_virtual_try_on: boolean;
    is_active: boolean;
    created_at: string;
    category?: {
        id: number;
        name: string;
    };
    images: ProductImage[];
    variants: Variant[];
}

interface Props {
    product: Product;
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produk', href: '/products' },
    { title: props.product.name, href: '#' },
]);

const formatRupiah = (angka: string) => {
    const num = parseFloat(angka);
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 bg-muted/20">
        <Head :title="`Detail Produk: ${product.name}`" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <!-- Header -->
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div class="flex gap-4 items-start">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border bg-background shadow-sm">
                    <img v-if="product.primary_image_url" :src="product.primary_image_url" :alt="product.name" class="h-full w-full object-cover" />
                    <ImageIcon v-else class="h-6 w-6 text-muted-foreground/50" />
                </div>
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">{{ product.name }}</h1>
                        <Badge :variant="product.is_active ? 'default' : 'secondary'" class="h-6">
                            {{ product.is_active ? 'Aktif' : 'Nonaktif' }}
                        </Badge>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-muted-foreground">
                        <span class="flex items-center gap-1.5">
                            <Layers class="h-4 w-4" />
                            {{ product.category?.name || 'Tanpa Kategori' }}
                        </span>
                        <span class="flex items-center gap-1.5" v-if="product.sku">
                            <Box class="h-4 w-4" />
                            SKU: {{ product.sku }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <Button variant="outline" as-child class="bg-background">
                    <Link href="/products">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="`/products/${product.id}/edit`">
                        <Edit class="mr-2 h-4 w-4" />
                        Edit Produk
                    </Link>
                </Button>
            </div>
        </div>

        <Tabs defaultValue="overview" class="w-full">
            <TabsList class="mb-4 grid w-full grid-cols-3 max-w-[400px]">
                <TabsTrigger value="overview">Informasi Utama</TabsTrigger>
                <TabsTrigger value="gallery">Galeri</TabsTrigger>
                <TabsTrigger value="variants">Varian</TabsTrigger>
            </TabsList>

            <!-- TAB 1: OVERVIEW -->
            <TabsContent value="overview" class="space-y-6">
                <!-- Metrics -->
                <div class="grid gap-4 md:grid-cols-4">
                    <Card class="bg-background">
                        <CardContent class="p-6">
                            <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                Harga Produk
                            </div>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-bold text-primary">{{ formatRupiah(product.price) }}</span>
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="bg-background">
                        <CardContent class="p-6">
                            <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                Stok Tersedia
                            </div>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-bold">{{ product.stock }}</span>
                                <span class="text-sm text-muted-foreground">Pcs</span>
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="bg-background">
                        <CardContent class="p-6">
                            <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                Fitur Virtual Try-On
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <ScanFace class="h-6 w-6" :class="product.is_virtual_try_on ? 'text-primary' : 'text-muted-foreground'" />
                                <span class="text-lg font-bold" :class="product.is_virtual_try_on ? 'text-foreground' : 'text-muted-foreground'">
                                    {{ product.is_virtual_try_on ? 'Didukung' : 'Tidak' }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="bg-background">
                        <CardContent class="p-6">
                            <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                Jumlah Varian
                            </div>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-2xl font-bold">{{ product.variants?.length || 0 }}</span>
                                <span class="text-sm text-muted-foreground">Tipe</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <Card class="bg-background">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Info class="h-5 w-5 text-muted-foreground" />
                                Spesifikasi
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                                <div>
                                    <dt class="font-medium text-muted-foreground">Kategori</dt>
                                    <dd class="mt-1 font-medium">{{ product.category?.name || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-muted-foreground">Berat Kemasan</dt>
                                    <dd class="mt-1 font-medium">{{ product.weight }} Gram</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-muted-foreground">SKU / Kode Produk</dt>
                                    <dd class="mt-1 font-medium">{{ product.sku || 'Belum diatur' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-muted-foreground">Ditambahkan Pada</dt>
                                    <dd class="mt-1 font-medium flex items-center gap-1.5">
                                        <Clock class="h-3.5 w-3.5 text-muted-foreground" />
                                        {{ formatDate(product.created_at) }}
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card class="bg-background h-full">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <FileText class="h-5 w-5 text-muted-foreground" />
                                Deskripsi Produk
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                                {{ product.description || 'Tidak ada deskripsi yang ditambahkan untuk produk ini.' }}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </TabsContent>

            <!-- TAB 2: GALLERY -->
            <TabsContent value="gallery" class="space-y-6">
                <Card class="bg-background">
                    <CardHeader>
                        <CardTitle>Gambar Produk</CardTitle>
                        <CardDescription>Gambar utama dan galeri pendukung.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col md:flex-row gap-6">
                            <div class="w-full max-w-sm space-y-3">
                                <h3 class="text-sm font-medium">Gambar Utama</h3>
                                <div class="aspect-square overflow-hidden rounded-xl border bg-muted flex items-center justify-center">
                                    <img v-if="product.primary_image_url" :src="product.primary_image_url" :alt="product.name" class="h-full w-full object-cover" />
                                    <ImageIcon v-else class="h-10 w-10 text-muted-foreground/30" />
                                </div>
                            </div>
                            
                            <Separator orientation="vertical" class="hidden md:block h-auto" />
                            <Separator class="md:hidden" />
                            
                            <div class="w-full space-y-3">
                                <h3 class="text-sm font-medium">Galeri Tambahan</h3>
                                <div v-if="product.images.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                                    <div v-for="image in product.images" :key="image.id" class="aspect-square overflow-hidden rounded-xl border bg-muted">
                                        <img :src="image.url" alt="Gallery image" class="h-full w-full object-cover" />
                                    </div>
                                </div>
                                <div v-else class="flex h-40 flex-col items-center justify-center rounded-xl border border-dashed text-muted-foreground">
                                    <ImageIcon class="mb-2 h-8 w-8 opacity-20" />
                                    <p class="text-sm">Tidak ada gambar tambahan.</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- TAB 3: VARIANTS -->
            <TabsContent value="variants" class="space-y-6">
                <Card class="bg-background">
                    <CardHeader>
                        <CardTitle>Daftar Varian</CardTitle>
                        <CardDescription>Pilihan ukuran, warna, atau jenis yang tersedia untuk produk ini.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="product.variants && product.variants.length > 0" class="grid gap-6 sm:grid-cols-2">
                            <div v-for="(variant, index) in product.variants" :key="index" class="space-y-3 rounded-lg border p-4 bg-muted/20">
                                <h4 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground">{{ variant.type }}</h4>
                                <div class="flex flex-wrap gap-2">
                                    <Badge v-for="(val, vIndex) in variant.values" :key="vIndex" variant="secondary" class="px-3 py-1 text-sm bg-background">
                                        {{ val }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-muted-foreground">
                            <Box class="mb-3 h-10 w-10 opacity-20" />
                            <p class="font-medium text-foreground">Tidak Ada Varian</p>
                            <p class="text-sm">Produk ini tidak memiliki varian.</p>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>
        </Tabs>
    </div>
</template>
