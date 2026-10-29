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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/composables/usePermissions';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    AlertTriangle,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Edit,
    Eye,
    Image as ImageIcon,
    Loader2,
    Plus,
    RefreshCw,
    Search,
    Sparkles,
    Trash2,
    UploadCloud,
    User,
    Users,
    Wand2,
    X,
} from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';

const { can } = usePermissions();

interface VirtualTryOnModel {
    id: number;
    name: string;
    gender: 'male' | 'female' | 'unisex';
    photo: string;
    photo_url: string | null;
    is_active: boolean;
    try_ons_count: number;
    created_at: string | null;
}

interface Pagination {
    data: VirtualTryOnModel[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface Props {
    models: Pagination;
    filters: {
        search?: string;
        gender?: string;
        is_active?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fashn AI', href: '#' },
    { title: 'Model Virtual Try-On', href: '/virtual-try-on-models' },
];

const filterState = reactive({
    search: props.filters.search || '',
    gender: props.filters.gender || 'all',
    is_active: props.filters.is_active || 'all',
    per_page: props.filters.per_page || '10',
});

const buildQuery = () => ({
    search: filterState.search || undefined,
    gender: filterState.gender === 'all' ? undefined : filterState.gender,
    is_active: filterState.is_active === 'all' ? undefined : filterState.is_active,
    per_page: filterState.per_page === '10' ? undefined : filterState.per_page,
});

const debouncedSearch = useDebounceFn(() => {
    router.get('/virtual-try-on-models', buildQuery(), {
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

const clearSearch = () => {
    filterState.search = '';
};

// Form Dialog Handling (Create & Edit)
const isFormDialogOpen = ref(false);
const editingModel = ref<VirtualTryOnModel | null>(null);
const photoPreviewUrl = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

// AI Generation State
const isGeneratingAi = ref(false);
const aiGenerateError = ref<string | null>(null);
const aiGenerateSuccess = ref<string | null>(null);

const form = useForm({
    name: '',
    gender: 'unisex' as 'male' | 'female' | 'unisex',
    creation_mode: 'manual' as 'manual' | 'ai',
    photo: null as File | null,
    prompt: 'Full body shot, woman wearing a white t-shirt and dark blue biker shorts',
    generated_photo_path: '',
    is_active: true,
    _method: 'POST',
});

const promptPresets = [
    {
        label: 'Wanita Kasual (T-Shirt & Shorts)',
        gender: 'female',
        prompt: 'Full body shot, woman wearing a white t-shirt and dark blue biker shorts, standing pose neutral studio background',
    },
    {
        label: 'Pria Formal (Kemeja Putih)',
        gender: 'male',
        prompt: 'Full body shot, handsome asian man wearing a plain white fitted shirt and dark trousers, standing pose neutral studio background',
    },
    {
        label: 'Wanita Elegan (Dress Simple)',
        gender: 'female',
        prompt: 'Full body shot, beautiful asian woman wearing a plain neutral dress, standing facing camera studio lighting',
    },
    {
        label: 'Pria Kasual (Kaos Polos)',
        gender: 'male',
        prompt: 'Full body shot, young asian man wearing a clean white crewneck t-shirt and light gray chinos, studio lighting',
    },
];

const applyPromptPreset = (preset: { label: string; gender: string; prompt: string }) => {
    form.prompt = preset.prompt;
    form.gender = preset.gender as any;
    if (!form.name || form.name.startsWith('Model ')) {
        form.name = preset.label;
    }
};

const openCreateDialog = () => {
    editingModel.value = null;
    form.reset();
    form.clearErrors();
    form._method = 'POST';
    form.creation_mode = 'manual';
    form.name = '';
    form.gender = 'unisex';
    form.photo = null;
    form.prompt = 'Full body shot, woman wearing a white t-shirt and dark blue biker shorts';
    form.generated_photo_path = '';
    form.is_active = true;
    photoPreviewUrl.value = null;
    aiGenerateError.value = null;
    aiGenerateSuccess.value = null;
    isFormDialogOpen.value = true;
};

const openEditDialog = (model: VirtualTryOnModel) => {
    editingModel.value = model;
    form.clearErrors();
    form._method = 'POST';
    form.creation_mode = 'manual';
    form.name = model.name;
    form.gender = model.gender;
    form.photo = null;
    form.generated_photo_path = '';
    form.is_active = model.is_active;
    photoPreviewUrl.value = model.photo_url;
    aiGenerateError.value = null;
    aiGenerateSuccess.value = null;
    isFormDialogOpen.value = true;
};

const handlePhotoSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.photo = file;
        form.generated_photo_path = '';
        photoPreviewUrl.value = URL.createObjectURL(file);
    }
};

const removeSelectedPhoto = () => {
    form.photo = null;
    form.generated_photo_path = '';
    if (editingModel.value) {
        photoPreviewUrl.value = editingModel.value.photo_url;
    } else {
        photoPreviewUrl.value = null;
    }
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

// Generate photo via Fashn AI endpoint
const generateWithAi = async () => {
    if (!form.prompt || !form.prompt.trim()) {
        aiGenerateError.value = 'Silakan tulis prompt deskripsi model terlebih dahulu.';
        return;
    }

    isGeneratingAi.value = true;
    aiGenerateError.value = null;
    aiGenerateSuccess.value = null;

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const response = await fetch('/virtual-try-on-models/generate-ai', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                prompt: form.prompt,
            }),
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Gagal men-generate model melalui Fashn AI.');
        }

        form.generated_photo_path = data.photo_path;
        form.photo = null;
        photoPreviewUrl.value = data.photo_url;
        aiGenerateSuccess.value = 'Foto model berhasil digenerate dan diunduh ke sistem storage!';
        form.clearErrors('photo');
    } catch (err: any) {
        aiGenerateError.value = err.message || 'Terjadi kesalahan saat memproses Fashn AI.';
    } finally {
        isGeneratingAi.value = false;
    }
};

const submitForm = () => {
    if (editingModel.value) {
        const updateData = new FormData();
        updateData.append('_method', 'PUT');
        updateData.append('name', form.name);
        updateData.append('gender', form.gender);
        updateData.append('is_active', form.is_active ? '1' : '0');
        if (form.photo) {
            updateData.append('photo', form.photo);
        } else if (form.generated_photo_path) {
            updateData.append('generated_photo_path', form.generated_photo_path);
        }

        router.post(`/virtual-try-on-models/${editingModel.value.id}`, updateData, {
            preserveScroll: true,
            onSuccess: () => {
                isFormDialogOpen.value = false;
                form.reset();
                editingModel.value = null;
                photoPreviewUrl.value = null;
            },
            onError: (errors) => {
                form.setError(errors as any);
            },
        });
    } else {
        form.post('/virtual-try-on-models', {
            preserveScroll: true,
            onSuccess: () => {
                isFormDialogOpen.value = false;
                form.reset();
                photoPreviewUrl.value = null;
            },
        });
    }
};

// Delete Dialog
const isDeleteDialogOpen = ref(false);
const modelToDelete = ref<VirtualTryOnModel | null>(null);

const confirmDelete = (model: VirtualTryOnModel) => {
    modelToDelete.value = model;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (!modelToDelete.value) return;

    router.delete(`/virtual-try-on-models/${modelToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            modelToDelete.value = null;
        },
    });
};

// Image Preview Lightbox
const isPreviewOpen = ref(false);
const previewImage = ref<{ url: string; title: string } | null>(null);

const openImagePreview = (url: string | null, title: string) => {
    if (!url) return;
    previewImage.value = { url, title };
    isPreviewOpen.value = true;
};

const formatDate = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const perPageOptions = ['10', '20', '30', '50'];
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 bg-muted/20">
        <Head title="Model Virtual Try-On - Fashn AI" />

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
                        <h1 class="text-xl font-semibold tracking-tight">Model Virtual Try-On</h1>
                        <p class="text-sm text-muted-foreground">
                            Kelola model mannequin/avatar bawaan (unggah manual atau generate otomatis dengan Fashn AI)
                        </p>
                    </div>
                </div>
            </div>

            <Button v-if="can('create virtual try on models')" @click="openCreateDialog" class="shrink-0">
                <Plus class="mr-2 h-4 w-4" />
                Tambah Model
            </Button>
        </div>

        <!-- DataTable Container -->
        <div class="rounded-xl border bg-card shadow-xs">
            <!-- Controls Bar -->
            <div class="flex flex-col gap-4 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-3">
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
                        <span>data</span>
                    </div>

                    <!-- Filter Gender -->
                    <Select v-model="filterState.gender">
                        <SelectTrigger class="h-8 w-[140px] text-xs">
                            <SelectValue placeholder="Semua Gender" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Gender</SelectItem>
                            <SelectItem value="male">Pria</SelectItem>
                            <SelectItem value="female">Wanita</SelectItem>
                            <SelectItem value="unisex">Unisex</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Filter Status -->
                    <Select v-model="filterState.is_active">
                        <SelectTrigger class="h-8 w-[130px] text-xs">
                            <SelectValue placeholder="Semua Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Status</SelectItem>
                            <SelectItem value="true">Aktif</SelectItem>
                            <SelectItem value="false">Nonaktif</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Search Input -->
                <div class="relative w-full sm:w-64">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="filterState.search"
                        placeholder="Cari nama model..."
                        class="h-9 pl-8 pr-8 text-sm"
                    />
                    <button
                        v-if="filterState.search"
                        @click="clearSearch"
                        class="absolute right-2.5 top-2.5 text-muted-foreground hover:text-foreground"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-muted/50 text-xs font-medium uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 w-16 text-center">Foto</th>
                            <th class="px-4 py-3">Nama Model</th>
                            <th class="px-4 py-3">Gender</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Penggunaan</th>
                            <th class="px-4 py-3">Dibuat Pada</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="models.data.length === 0">
                            <td colspan="7" class="py-12 text-center text-muted-foreground">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <Sparkles class="h-8 w-8 text-muted-foreground/50" />
                                    <p class="font-medium">Belum ada model virtual try-on</p>
                                    <p class="text-xs">Klik "Tambah Model" untuk mengunggah atau men-generate foto model baru.</p>
                                </div>
                            </td>
                        </tr>

                        <tr
                            v-for="model in models.data"
                            :key="model.id"
                            class="hover:bg-muted/40 transition-colors"
                        >
                            <!-- Thumbnail -->
                            <td class="px-4 py-3 text-center">
                                <button
                                    v-if="model.photo_url"
                                    @click="openImagePreview(model.photo_url, model.name)"
                                    class="group relative mx-auto h-12 w-12 overflow-hidden rounded-md border bg-muted focus:outline-none focus:ring-2 focus:ring-primary"
                                    title="Klik untuk memperbesar"
                                >
                                    <img
                                        :src="model.photo_url"
                                        :alt="model.name"
                                        class="h-full w-full object-cover object-top transition group-hover:scale-105"
                                    />
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <Eye class="h-4 w-4 text-white" />
                                    </div>
                                </button>
                                <div
                                    v-else
                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-md border bg-muted text-muted-foreground"
                                >
                                    <ImageIcon class="h-5 w-5" />
                                </div>
                            </td>

                            <!-- Nama -->
                            <td class="px-4 py-3">
                                <div class="font-medium text-foreground">{{ model.name }}</div>
                                <div class="text-xs text-muted-foreground">ID: #{{ model.id }}</div>
                            </td>

                            <!-- Gender Badge -->
                            <td class="px-4 py-3">
                                <Badge
                                    v-if="model.gender === 'male'"
                                    variant="outline"
                                    class="bg-blue-500/10 text-blue-700 border-blue-200 dark:text-blue-400 dark:border-blue-800"
                                >
                                    Pria
                                </Badge>
                                <Badge
                                    v-else-if="model.gender === 'female'"
                                    variant="outline"
                                    class="bg-rose-500/10 text-rose-700 border-rose-200 dark:text-rose-400 dark:border-rose-800"
                                >
                                    Wanita
                                </Badge>
                                <Badge
                                    v-else
                                    variant="outline"
                                    class="bg-purple-500/10 text-purple-700 border-purple-200 dark:text-purple-400 dark:border-purple-800"
                                >
                                    Unisex
                                </Badge>
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-3 text-center">
                                <Badge
                                    v-if="model.is_active"
                                    variant="outline"
                                    class="bg-emerald-500/10 text-emerald-700 border-emerald-200 dark:text-emerald-400 dark:border-emerald-800"
                                >
                                    Aktif
                                </Badge>
                                <Badge
                                    v-else
                                    variant="outline"
                                    class="bg-muted text-muted-foreground"
                                >
                                    Nonaktif
                                </Badge>
                            </td>

                            <!-- Try-ons count -->
                            <td class="px-4 py-3 text-center">
                                <Badge variant="secondary" class="font-normal text-xs">
                                    {{ model.try_ons_count }} Try-On
                                </Badge>
                            </td>

                            <!-- Tanggal -->
                            <td class="px-4 py-3 text-xs text-muted-foreground">
                                {{ formatDate(model.created_at) }}
                            </td>

                            <!-- Aksi -->
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button
                                        v-if="can('edit virtual try on models')"
                                        variant="ghost"
                                        size="icon"
                                        @click="openEditDialog(model)"
                                        title="Edit Model"
                                    >
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        v-if="can('delete virtual try on models')"
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:bg-destructive/10"
                                        @click="confirmDelete(model)"
                                        title="Hapus Model"
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
                v-if="models.total > 0"
                class="flex flex-col gap-4 border-t p-4 sm:flex-row sm:items-center sm:justify-between text-xs text-muted-foreground"
            >
                <div>
                    Menampilkan <span class="font-medium text-foreground">{{ models.from || 0 }}</span> sampai
                    <span class="font-medium text-foreground">{{ models.to || 0 }}</span> dari
                    <span class="font-medium text-foreground">{{ models.total }}</span> model
                </div>

                <div class="flex items-center gap-1">
                    <Button
                        v-for="link in models.links"
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

        <!-- Create / Edit Dialog -->
        <Dialog v-model:open="isFormDialogOpen">
            <DialogContent class="sm:max-w-xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Sparkles class="h-5 w-5 text-primary" />
                        {{ editingModel ? 'Edit Model Virtual Try-On' : 'Tambah Model Virtual Try-On' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ editingModel ? 'Perbarui informasi dan foto model mannequin.' : 'Daftarkan model mannequin baru secara manual atau generate otomatis menggunakan AI Fashn.ai.' }}
                    </DialogDescription>
                </DialogHeader>

                <!-- Method Switcher (Manual Upload vs AI Generate) - Hanya pada Mode Create -->
                <div v-if="!editingModel" class="space-y-2">
                    <Label class="text-xs font-semibold text-muted-foreground uppercase">Metode Pembuatan Model</Label>
                    <Tabs v-model="form.creation_mode" class="w-full">
                        <TabsList class="grid w-full grid-cols-2">
                            <TabsTrigger value="manual" class="gap-2 text-xs">
                                <UploadCloud class="h-3.5 w-3.5" />
                                Unggah Manual (File)
                            </TabsTrigger>
                            <TabsTrigger value="ai" class="gap-2 text-xs">
                                <Wand2 class="h-3.5 w-3.5 text-purple-600" />
                                Generate Otomatis (Fashn AI)
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4 py-2">
                    <!-- SECTION 1: PHOTO SOURCE -->

                    <!-- A. Mode Manual Upload -->
                    <div v-if="form.creation_mode === 'manual'" class="space-y-2">
                        <Label>Foto Model Mannequin / Avatar <span class="text-destructive">*</span></Label>
                        <div class="flex items-center gap-4">
                            <!-- Preview Box -->
                            <div
                                class="relative h-28 w-28 shrink-0 overflow-hidden rounded-lg border-2 border-dashed bg-muted flex items-center justify-center group"
                            >
                                <img
                                    v-if="photoPreviewUrl"
                                    :src="photoPreviewUrl"
                                    alt="Preview"
                                    class="h-full w-full object-cover object-top"
                                />
                                <div v-else class="text-center p-2 text-muted-foreground">
                                    <UploadCloud class="mx-auto h-6 w-6 mb-1 opacity-60" />
                                    <span class="text-[10px]">Pilih Foto</span>
                                </div>
                            </div>

                            <!-- Upload Action -->
                            <div class="flex-1 space-y-2">
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="hidden"
                                    @change="handlePhotoSelect"
                                />
                                <div class="flex gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="fileInput?.click()"
                                    >
                                        <UploadCloud class="mr-2 h-4 w-4" />
                                        {{ photoPreviewUrl ? 'Ganti Foto' : 'Unggah Foto' }}
                                    </Button>
                                    <Button
                                        v-if="form.photo"
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive"
                                        @click="removeSelectedPhoto"
                                    >
                                        Batal
                                    </Button>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Format: JPG, PNG, WEBP (maks. 5MB). Disarankan foto seluruh badan (full body) berlatar polos.
                                </p>
                            </div>
                        </div>
                        <p v-if="form.errors.photo" class="text-xs text-destructive">
                            {{ form.errors.photo }}
                        </p>
                    </div>

                    <!-- B. Mode Generate Otomatis AI (Fashn AI) -->
                    <div v-else class="space-y-3 rounded-xl border p-4 bg-purple-500/5 border-purple-200 dark:border-purple-900/50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-sm font-semibold text-purple-700 dark:text-purple-400">
                                <Sparkles class="h-4 w-4" />
                                Fashn AI Model Generator
                            </div>
                            <span class="text-[11px] text-muted-foreground font-mono">model-create</span>
                        </div>

                        <!-- Prompt Input -->
                        <div class="space-y-1.5">
                            <Label for="ai-prompt" class="text-xs font-medium">
                                Prompt Deskripsi Model <span class="text-destructive">*</span>
                            </Label>
                            <Textarea
                                id="ai-prompt"
                                v-model="form.prompt"
                                rows="3"
                                placeholder="Tuliskan deskripsi model, contoh: Full body shot, woman wearing a white t-shirt and dark blue biker shorts..."
                                class="text-xs bg-background"
                            />
                            <p v-if="form.errors.prompt" class="text-xs text-destructive">
                                {{ form.errors.prompt }}
                            </p>
                        </div>

                        <!-- Quick Prompt Presets -->
                        <div class="space-y-1">
                            <span class="text-[11px] text-muted-foreground">Rekomendasi Prompt Cepat:</span>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="preset in promptPresets"
                                    :key="preset.label"
                                    type="button"
                                    @click="applyPromptPreset(preset)"
                                    class="rounded-full border bg-background px-2.5 py-0.5 text-[11px] text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                                >
                                    {{ preset.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Generate Button & Preview -->
                        <div class="flex items-center gap-4 pt-1">
                            <!-- Photo Preview Box -->
                            <div
                                class="relative h-28 w-28 shrink-0 overflow-hidden rounded-lg border-2 border-dashed bg-muted flex items-center justify-center group"
                            >
                                <img
                                    v-if="photoPreviewUrl"
                                    :src="photoPreviewUrl"
                                    alt="AI Preview"
                                    class="h-full w-full object-cover object-top"
                                />
                                <div v-else class="text-center p-2 text-muted-foreground">
                                    <Sparkles class="mx-auto h-6 w-6 mb-1 opacity-60 text-purple-600" />
                                    <span class="text-[10px]">Preview AI</span>
                                </div>

                                <div
                                    v-if="isGeneratingAi"
                                    class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center text-white p-2 text-center"
                                >
                                    <Loader2 class="h-6 w-6 animate-spin mb-1 text-purple-400" />
                                    <span class="text-[10px]">Men-generate...</span>
                                </div>
                            </div>

                            <div class="flex-1 space-y-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    class="w-full bg-purple-600 hover:bg-purple-700 text-white"
                                    :disabled="isGeneratingAi || !form.prompt"
                                    @click="generateWithAi"
                                >
                                    <Loader2 v-if="isGeneratingAi" class="mr-2 h-4 w-4 animate-spin" />
                                    <Sparkles v-else class="mr-2 h-4 w-4" />
                                    {{ photoPreviewUrl ? 'Generate Ulang Gambar' : 'Generate Gambar dengan AI' }}
                                </Button>
                                <p class="text-[11px] text-muted-foreground leading-snug">
                                    Gambar akan digenerate via API Fashn.ai dan otomatis diunduh serta disimpan ke storage lokal sistem Anda.
                                </p>
                            </div>
                        </div>

                        <!-- Feedback Messages -->
                        <div
                            v-if="aiGenerateSuccess"
                            class="flex items-center gap-2 p-2 rounded bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 text-xs border border-emerald-200"
                        >
                            <CheckCircle2 class="h-4 w-4 shrink-0" />
                            <span>{{ aiGenerateSuccess }}</span>
                        </div>
                        <div
                            v-if="aiGenerateError"
                            class="flex items-center gap-2 p-2 rounded bg-destructive/10 text-destructive text-xs border border-destructive/20"
                        >
                            <AlertTriangle class="h-4 w-4 shrink-0" />
                            <span>{{ aiGenerateError }}</span>
                        </div>
                    </div>

                    <!-- SECTION 2: METADATA (Nama & Gender) -->
                    <!-- Nama Model -->
                    <div class="space-y-2">
                        <Label for="model-name">Nama Model <span class="text-destructive">*</span></Label>
                        <Input
                            id="model-name"
                            v-model="form.name"
                            placeholder="Contoh: Model Pria Formal (Dewasa)"
                            required
                        />
                        <p v-if="form.errors.name" class="text-xs text-destructive">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Gender Model -->
                    <div class="space-y-2">
                        <Label>Gender Model <span class="text-destructive">*</span></Label>
                        <Select v-model="form.gender">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih gender" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="male">Pria (Male)</SelectItem>
                                <SelectItem value="female">Wanita (Female)</SelectItem>
                                <SelectItem value="unisex">Unisex</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.gender" class="text-xs text-destructive">
                            {{ form.errors.gender }}
                        </p>
                    </div>

                    <!-- Status Aktif Switch -->
                    <div class="flex items-center justify-between rounded-lg border p-3">
                        <div class="space-y-0.5">
                            <Label class="text-sm font-medium">Status Aktif</Label>
                            <p class="text-xs text-muted-foreground">
                                Model aktif akan tampil pada pilihan avatar virtual try-on pengguna.
                            </p>
                        </div>
                        <Switch v-model:checked="form.is_active" />
                    </div>

                    <DialogFooter class="pt-4 border-t">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isFormDialogOpen = false"
                            :disabled="form.processing || isGeneratingAi"
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing || isGeneratingAi || (!photoPreviewUrl && !form.photo && !form.prompt)"
                        >
                            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            {{ editingModel ? 'Simpan Perubahan' : 'Tambah Model' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="isDeleteDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-destructive">
                        <AlertTriangle class="h-5 w-5" />
                        Konfirmasi Hapus Model
                    </DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus model
                        <strong class="text-foreground">"{{ modelToDelete?.name }}"</strong>? Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="isDeleteDialogOpen = false">
                        Batal
                    </Button>
                    <Button variant="destructive" @click="executeDelete">
                        Ya, Hapus Model
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Image Lightbox Modal -->
        <Dialog v-model:open="isPreviewOpen">
            <DialogContent class="sm:max-w-xl p-3">
                <DialogHeader class="px-2 pt-2 pb-0">
                    <DialogTitle class="text-base">{{ previewImage?.title }}</DialogTitle>
                </DialogHeader>
                <div class="relative overflow-hidden rounded-lg bg-black/5 flex items-center justify-center max-h-[75vh]">
                    <img
                        v-if="previewImage"
                        :src="previewImage.url"
                        :alt="previewImage.title"
                        class="max-h-[70vh] w-auto object-contain rounded"
                    />
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
