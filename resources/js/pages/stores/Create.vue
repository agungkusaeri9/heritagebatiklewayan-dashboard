<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { UploadCloud, X, Store as StoreIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Toko', href: '/stores' },
    { title: 'Tambah Toko', href: '/stores/create' },
];

const form = useForm({
    name: '',
    email: '',
    phone: '',
    whatsapp: '',
    logo: null as File | null,
    description: '',
    seller_name: '',
    seller_username: '',
    seller_email: '',
    seller_password: '',
});

const logoPreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const removeLogo = () => {
    form.logo = null;
    logoPreview.value = null;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submit = () => {
    form.post('/stores');
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Tambah Toko" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">
                Tambah Toko Baru
            </h1>
            <p class="text-sm text-muted-foreground">
                Tambahkan profil toko baru sekaligus buat atau hubungkan akun Seller (Admin Store).
            </p>
        </div>

        <form @submit.prevent="submit" class="grid gap-6">
            <Card class="w-full">
                <CardHeader>
                    <CardTitle>Informasi Toko</CardTitle>
                    <CardDescription>
                        Lengkapi identitas, kontak, logo, dan deskripsi toko.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-6">
                    <!-- Logo Upload -->
                    <div class="grid gap-2">
                        <Label>Logo Toko</Label>
                        <div class="flex flex-col items-start gap-4 sm:flex-row">
                            <div
                                class="relative flex h-32 w-32 shrink-0 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed bg-muted/40 transition-colors"
                                :class="{ 'border-primary/50 bg-primary/5': logoPreview }"
                            >
                                <img
                                    v-if="logoPreview"
                                    :src="logoPreview"
                                    alt="Preview Logo"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex flex-col items-center justify-center gap-1.5 p-2 text-center text-muted-foreground"
                                >
                                    <StoreIcon class="h-8 w-8 text-muted-foreground/60" />
                                    <span class="text-[11px]">Belum ada logo</span>
                                </div>

                                <button
                                    v-if="logoPreview"
                                    type="button"
                                    @click="removeLogo"
                                    class="absolute top-1.5 right-1.5 rounded-full bg-background/80 p-1 text-muted-foreground backdrop-blur-xs transition-colors hover:bg-destructive hover:text-destructive-foreground"
                                    title="Hapus logo"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <div class="flex flex-1 flex-col gap-2">
                                <input
                                    ref="fileInput"
                                    type="file"
                                    id="logo"
                                    accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                                    class="hidden"
                                    @change="handleFileChange"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="w-fit"
                                    @click="fileInput?.click()"
                                >
                                    <UploadCloud class="mr-2 h-4 w-4" />
                                    Pilih File Logo
                                </Button>
                                <p class="text-xs text-muted-foreground">
                                    Format yang didukung: PNG, JPG, JPEG, WEBP, atau SVG. Ukuran maksimal 2MB.
                                </p>
                                <p v-if="form.errors.logo" class="text-xs font-medium text-destructive">
                                    {{ form.errors.logo }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="name">Nama Toko <span class="text-destructive">*</span></Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Contoh: Batik Putra Laweyan"
                                required
                            />
                            <p v-if="form.errors.name" class="text-xs text-destructive">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email Toko (Opsional)</Label>
                            <Input
                                id="email"
                                type="email"
                                v-model="form.email"
                                placeholder="contoh: toko@batiklaweyan.com"
                            />
                            <p v-if="form.errors.email" class="text-xs text-destructive">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone">Nomor Telepon <span class="text-destructive">*</span></Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                placeholder="Contoh: 0271712345"
                                required
                            />
                            <p v-if="form.errors.phone" class="text-xs text-destructive">
                                {{ form.errors.phone }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="whatsapp">Nomor WhatsApp <span class="text-destructive">*</span></Label>
                            <Input
                                id="whatsapp"
                                v-model="form.whatsapp"
                                placeholder="Contoh: 6281234567890"
                                required
                            />
                            <p v-if="form.errors.whatsapp" class="text-xs text-destructive">
                                {{ form.errors.whatsapp }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Deskripsi Toko</Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            placeholder="Ceritakan singkat mengenai profil, koleksi batik unggulan, atau alamat toko..."
                        />
                        <p v-if="form.errors.description" class="text-xs text-destructive">
                            {{ form.errors.description }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card class="w-full">
                <CardHeader>
                    <CardTitle>Akun Seller / Admin Store</CardTitle>
                    <CardDescription>
                        Akun seller baru ini akan langsung dibuat dan otomatis mendapatkan peran <strong>admin store</strong>.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="seller_name">Nama Lengkap Seller <span class="text-destructive">*</span></Label>
                            <Input
                                id="seller_name"
                                v-model="form.seller_name"
                                placeholder="Masukkan nama pengelola toko"
                                required
                            />
                            <p v-if="form.errors.seller_name" class="text-xs text-destructive">
                                {{ form.errors.seller_name }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="seller_username">Username Seller <span class="text-destructive">*</span></Label>
                            <Input
                                id="seller_username"
                                v-model="form.seller_username"
                                placeholder="Contoh: adminputralaweyan"
                                required
                            />
                            <p v-if="form.errors.seller_username" class="text-xs text-destructive">
                                {{ form.errors.seller_username }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="seller_email">Email Seller (Opsional)</Label>
                            <Input
                                id="seller_email"
                                type="email"
                                v-model="form.seller_email"
                                placeholder="contoh: seller@batiklaweyan.com"
                            />
                            <p v-if="form.errors.seller_email" class="text-xs text-destructive">
                                {{ form.errors.seller_email }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="seller_password">Kata Sandi Seller <span class="text-destructive">*</span></Label>
                            <Input
                                id="seller_password"
                                type="password"
                                v-model="form.seller_password"
                                placeholder="Minimal 8 karakter"
                                required
                            />
                            <p v-if="form.errors.seller_password" class="text-xs text-destructive">
                                {{ form.errors.seller_password }}
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 border-t pt-4">
                        <Button type="button" variant="outline" as-child>
                            <Link href="/stores">Batal</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Toko & Seller' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    </div>
</template>
