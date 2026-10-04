<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/tabs';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Image as ImageIcon, Loader2, Save, Trash2, Store, Truck } from 'lucide-vue-next';
import { ref } from 'vue';

interface Courier {
    id: number;
    name: string;
    code: string;
    is_active: boolean;
    logo: string | null;
}

interface StoreSetting {
    id: number;
    name: string;
    logo: string | null;
    whatsapp: string | null;
    description: string | null;
    address: string | null;
}

interface Province {
    province_id: number;
    province_name: string;
}

interface City {
    city_id: number;
    city_name: string;
}

interface Subdistrict {
    subdistrict_id: number;
    subdistrict_name: string;
}

interface Props {
    setting: StoreSetting;
    couriers: Courier[];
    provinces: Province[];
}

const props = defineProps<Props>();

const toggleCourier = (courierId: number) => {
    router.post(`/couriers/${courierId}/toggle`, {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pengaturan', href: '#' },
    { title: 'Toko', href: '/store-settings' },
];

const fileInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(props.setting.logo ? `/storage/${props.setting.logo}` : null);

const form = useForm({
    name: props.setting.name || '',
    whatsapp: props.setting.whatsapp || '',
    description: props.setting.description || '',
    address: props.setting.address || '',
    province_id: props.setting.province_id?.toString() || '',
    city_id: props.setting.city_id?.toString() || '',
    subdistrict_id: props.setting.subdistrict_id?.toString() || '',
    logo: null as File | null | undefined, // undefined means no change, null means delete
    logo_file: null as File | null, // explicitly for new uploads
});

const cities = ref<City[]>([]);
const subdistricts = ref<Subdistrict[]>([]);

import { onMounted, watch } from 'vue';

const loadCities = async (provinceId: string) => {
    if (!provinceId) {
        cities.value = [];
        return;
    }
    const response = await fetch(`/api/locations/cities?province_id=${provinceId}`);
    cities.value = await response.json();
};

const loadSubdistricts = async (cityId: string) => {
    if (!cityId) {
        subdistricts.value = [];
        return;
    }
    const response = await fetch(`/api/locations/subdistricts?city_id=${cityId}`);
    subdistricts.value = await response.json();
};

onMounted(() => {
    if (form.province_id) loadCities(form.province_id);
    if (form.city_id) loadSubdistricts(form.city_id);
});

const handleLogoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.logo_file = target.files[0];
        form.logo = undefined; // reset the delete flag
        previewUrl.value = URL.createObjectURL(target.files[0]);
    }
};

const removeLogo = () => {
    form.logo_file = null;
    form.logo = null; // sets logo to null so backend deletes it
    previewUrl.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submitForm = () => {
    form.post('/store-settings', {
        preserveScroll: true,
        onSuccess: () => {
            if (form.logo === null) {
                form.logo = undefined;
            }
        },
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Pengaturan Toko" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <Store class="h-6 w-6 text-primary" />
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Pengaturan Toko</h1>
                </div>
                <p class="text-sm text-muted-foreground">
                    Kelola informasi dasar toko Anda seperti nama, logo, kontak, dan alamat.
                </p>
            </div>
        </div>

        <div class="w-full">
            <form @submit.prevent="submitForm">
                <Tabs defaultValue="general" class="w-full">
                    <TabsList class="grid w-full grid-cols-3">
                        <TabsTrigger value="general">Informasi Umum</TabsTrigger>
                        <TabsTrigger value="contact">Kontak & Alamat</TabsTrigger>
                        <TabsTrigger value="couriers">Kurir Pengiriman</TabsTrigger>
                    </TabsList>

                    <!-- TAB: Informasi Umum -->
                    <TabsContent value="general">
                        <div class="rounded-xl border bg-card text-card-foreground shadow-sm mt-4 p-6 space-y-6">
                            <!-- Logo Section -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-medium border-b pb-2">Logo Toko</h3>
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                                    <div class="flex h-32 w-32 shrink-0 items-center justify-center overflow-hidden rounded-lg border-2 border-dashed bg-muted/50">
                                        <img
                                            v-if="previewUrl"
                                            :src="previewUrl"
                                            alt="Preview Logo"
                                            class="h-full w-full object-contain p-2"
                                        />
                                        <div v-else class="flex flex-col items-center gap-2 text-muted-foreground">
                                            <ImageIcon class="h-8 w-8" />
                                            <span class="text-xs">Belum ada logo</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex gap-2">
                                            <Input
                                                id="logo_file"
                                                type="file"
                                                accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                                                class="hidden"
                                                ref="fileInput"
                                                @change="handleLogoChange"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                @click="() => fileInput?.click()"
                                            >
                                                Pilih Gambar Baru
                                            </Button>
                                            <Button
                                                v-if="previewUrl"
                                                type="button"
                                                variant="destructive"
                                                size="icon"
                                                @click="removeLogo"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                        <p class="text-xs text-muted-foreground">
                                            Format yang didukung: JPG, PNG, SVG. Ukuran maksimal: 2MB.
                                        </p>
                                        <p v-if="form.errors.logo_file" class="text-sm text-destructive">{{ form.errors.logo_file }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Name & Description -->
                            <div class="space-y-4 pt-2">
                                <div class="space-y-2">
                                    <Label for="name">Nama Toko <span class="text-destructive">*</span></Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        placeholder="Masukkan nama toko"
                                        :class="{ 'border-destructive': form.errors.name }"
                                    />
                                    <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="description">Deskripsi Singkat Toko</Label>
                                    <Textarea
                                        id="description"
                                        v-model="form.description"
                                        placeholder="Jelaskan secara singkat tentang toko Anda..."
                                        class="min-h-[120px]"
                                        :class="{ 'border-destructive': form.errors.description }"
                                    />
                                    <p v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</p>
                                </div>
                            </div>
                        </div>
                    </TabsContent>

                    <!-- TAB: Kontak & Alamat -->
                    <TabsContent value="contact">
                        <div class="rounded-xl border bg-card text-card-foreground shadow-sm mt-4 p-6 space-y-6">
                            <h3 class="text-lg font-medium border-b pb-2">Kontak & Alamat</h3>
                            
                            <div class="space-y-2">
                                <Label for="whatsapp">Nomor WhatsApp</Label>
                                <Input
                                    id="whatsapp"
                                    v-model="form.whatsapp"
                                    placeholder="Contoh: 6281234567890"
                                    :class="{ 'border-destructive': form.errors.whatsapp }"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Gunakan format awalan 62xxx. Akan digunakan untuk fitur chat pembeli (mobile / web).
                                </p>
                                <p v-if="form.errors.whatsapp" class="text-sm text-destructive">{{ form.errors.whatsapp }}</p>
                            </div>

                            <div class="grid gap-6 md:grid-cols-3">
                                <div class="space-y-2">
                                    <Label for="province_id">Provinsi (Asal Pengiriman) <span class="text-destructive">*</span></Label>
                                    <Select 
                                        v-model="form.province_id" 
                                        @update:modelValue="(val) => { form.city_id = ''; form.subdistrict_id = ''; subdistricts = []; loadCities(val); }"
                                    >
                                        <SelectTrigger :class="{ 'border-destructive': form.errors.province_id }">
                                            <SelectValue placeholder="Pilih Provinsi" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="province in provinces" :key="province.province_id" :value="province.province_id.toString()">
                                                {{ province.province_name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.province_id" class="text-sm text-destructive">{{ form.errors.province_id }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="city_id">Kota / Kabupaten <span class="text-destructive">*</span></Label>
                                    <Select 
                                        v-model="form.city_id" 
                                        :disabled="!form.province_id || cities.length === 0"
                                        @update:modelValue="(val) => { form.subdistrict_id = ''; loadSubdistricts(val); }"
                                    >
                                        <SelectTrigger :class="{ 'border-destructive': form.errors.city_id }">
                                            <SelectValue placeholder="Pilih Kota/Kabupaten" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="city in cities" :key="city.city_id" :value="city.city_id.toString()">
                                                {{ city.city_name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.city_id" class="text-sm text-destructive">{{ form.errors.city_id }}</p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="subdistrict_id">Kecamatan <span class="text-destructive">*</span></Label>
                                    <Select 
                                        v-model="form.subdistrict_id" 
                                        :disabled="!form.city_id || subdistricts.length === 0"
                                    >
                                        <SelectTrigger :class="{ 'border-destructive': form.errors.subdistrict_id }">
                                            <SelectValue placeholder="Pilih Kecamatan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="subdistrict in subdistricts" :key="subdistrict.subdistrict_id" :value="subdistrict.subdistrict_id.toString()">
                                                {{ subdistrict.subdistrict_name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.subdistrict_id" class="text-sm text-destructive">{{ form.errors.subdistrict_id }}</p>
                                </div>
                            </div>

                            <div class="space-y-2 pt-2">
                                <Label for="address">Alamat Lengkap Toko</Label>
                                <Textarea
                                    id="address"
                                    v-model="form.address"
                                    placeholder="Jalan, RT/RW, Kelurahan, Kecamatan, Kota, Kode Pos"
                                    class="min-h-[140px]"
                                    :class="{ 'border-destructive': form.errors.address }"
                                />
                                <p v-if="form.errors.address" class="text-sm text-destructive">{{ form.errors.address }}</p>
                            </div>
                        </div>
                    </TabsContent>
                    <!-- TAB: Kurir Pengiriman -->
                    <TabsContent value="couriers">
                        <div class="rounded-xl border bg-card text-card-foreground shadow-sm mt-4 p-6 space-y-6">
                            <h3 class="text-lg font-medium border-b pb-2 flex items-center gap-2">
                                <Truck class="h-5 w-5" /> Kurir Pengiriman
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Aktifkan atau nonaktifkan kurir yang ingin Anda sediakan untuk pengiriman pesanan.
                            </p>

                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div 
                                    v-for="courier in couriers" 
                                    :key="courier.id"
                                    class="flex items-center justify-between space-x-4 rounded-lg border p-4 shadow-sm"
                                >
                                    <div class="flex items-center space-x-4">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-md border bg-muted/50 p-1">
                                            <img v-if="courier.logo" :src="`/storage/${courier.logo}`" :alt="courier.name" class="max-h-full max-w-full object-contain" />
                                            <Truck v-else class="h-6 w-6 text-muted-foreground" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium leading-none">{{ courier.name }}</p>
                                            <p class="text-xs text-muted-foreground mt-1 uppercase">{{ courier.code }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-muted-foreground">{{ courier.is_active ? 'ON' : 'OFF' }}</span>
                                        <label class="relative flex items-center cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                v-model="courier.is_active"
                                                @change="toggleCourier(courier.id)"
                                                class="peer sr-only"
                                            />
                                            <div class="peer h-6 w-11 rounded-full bg-input after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabsContent>
                </Tabs>

                <!-- Floating Footer / Submit (Global for both tabs) -->
                <div class="mt-6 flex items-center justify-end gap-4 rounded-xl border bg-card p-4 shadow-sm">
                    <Button
                        type="button"
                        variant="outline"
                        @click="() => form.reset()"
                        :disabled="form.processing"
                    >
                        Reset
                    </Button>
                    <Button type="submit" :disabled="form.processing" class="gap-2">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        Simpan Pengaturan
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
