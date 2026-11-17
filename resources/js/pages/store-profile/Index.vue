<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { UploadCloud, X, Store as StoreIcon, Clock } from 'lucide-vue-next';
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
import { usePermissions } from '@/composables/usePermissions';
import type { BreadcrumbItem } from '@/types';

const { can } = usePermissions();

interface StoreProfile {
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
}

const props = defineProps<{
    store: StoreProfile | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Profile Toko', href: '/dashboard/store-profile' },
];

const form = useForm({
    _method: 'PUT',
    name: props.store?.name || '',
    email: props.store?.email || '',
    phone: props.store?.phone || '',
    whatsapp: props.store?.whatsapp || '',
    logo: null as File | null,
    description: props.store?.description || '',
});

const logoPreview = ref<string | null>(props.store?.logo_url || null);
const fileInput = ref<HTMLInputElement | null>(null);

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;

    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const removeSelectedLogo = () => {
    form.logo = null;
    logoPreview.value = props.store?.logo_url || null;

    if (fileInput.value) {
        fileInput.value.value = '';
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

const submit = () => {
    if (!can('edit store profile')) {
        return;
    }

    form.post('/dashboard/store-profile');
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Profile Toko" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                Profile Toko
            </h1>
            <p class="text-sm text-muted-foreground">
                Kelola informasi profil, kontak, logo, dan deskripsi toko Anda.
            </p>
        </div>

        <Card v-if="!store" class="w-full">
            <CardContent class="flex flex-col items-center justify-center gap-3 p-10 text-center">
                <div class="rounded-full bg-muted p-4">
                    <StoreIcon class="h-8 w-8 text-muted-foreground" />
                </div>
                <div class="space-y-1">
                    <p class="font-semibold text-foreground">Belum Ada Toko Terhubung</p>
                    <p class="text-sm text-muted-foreground">
                        Akun Anda saat ini belum terhubung dengan profil toko mana pun.
                    </p>
                </div>
                <Button v-if="can('view stores')" variant="outline" as-child class="mt-2">
                    <Link href="/dashboard/stores">Kelola Data Toko</Link>
                </Button>
            </CardContent>
        </Card>

        <form v-else @submit.prevent="submit" class="grid gap-6">
            <Card class="w-full">
                <CardHeader>
                    <CardTitle>Informasi Profile Toko</CardTitle>
                    <CardDescription>
                        Perbarui detail kontak dan tampilan profil toko Anda.
                    </CardDescription>
                </CardHeader>
                <CardContent class="grid gap-6">
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
                                    v-if="form.logo && can('edit store profile')"
                                    type="button"
                                    @click="removeSelectedLogo"
                                    class="absolute top-1.5 right-1.5 rounded-full bg-background/80 p-1 text-muted-foreground backdrop-blur-xs transition-colors hover:bg-destructive hover:text-destructive-foreground"
                                    title="Batalkan logo baru"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <div v-if="can('edit store profile')" class="flex flex-1 flex-col gap-2">
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
                                    {{ logoPreview ? 'Ganti File Logo' : 'Pilih File Logo' }}
                                </Button>
                                <p class="text-xs text-muted-foreground">
                                    Format yang didukung: PNG, JPG, JPEG, WEBP, atau SVG. Maksimal 2MB.
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
                                placeholder="Nama toko"
                                :disabled="!can('edit store profile')"
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
                                :disabled="!can('edit store profile')"
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
                                :disabled="!can('edit store profile')"
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
                                :disabled="!can('edit store profile')"
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
                            placeholder="Ceritakan singkat mengenai profil toko Anda..."
                            :disabled="!can('edit store profile')"
                        />
                        <p v-if="form.errors.description" class="text-xs text-destructive">
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-6 rounded-lg border bg-muted/20 px-4 py-3 text-xs text-muted-foreground">
                        <div class="flex items-center gap-1.5">
                            <Clock class="h-3.5 w-3.5" />
                            <span><strong class="font-medium text-foreground">Dibuat pada:</strong> {{ formatDateTime(store.created_at) }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <Clock class="h-3.5 w-3.5" />
                            <span><strong class="font-medium text-foreground">Terakhir diperbarui:</strong> {{ formatDateTime(store.updated_at) }}</span>
                        </div>
                    </div>

                    <div v-if="can('edit store profile')" class="flex justify-end gap-4 border-t pt-4">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Profile Toko' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    </div>
</template>
