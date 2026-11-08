<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Loader2, Plus, Star, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface SelectedExample {
    id: number;
    product_name?: string | null;
    model_name?: string | null;
    photo_url?: string | null;
    video_url?: string | null;
}

interface AvailableExample {
    id: number;
    label: string;
    photo_url?: string | null;
}

interface Props {
    selected: SelectedExample[];
    available: AvailableExample[];
    maxExamples: number;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pengaturan', href: '#' },
    { title: 'Landing Page', href: '/landing-page-settings' },
];

const form = useForm<{ examples: number[] }>({
    examples: props.selected.map((item) => item.id),
});

const isFull = computed(() => form.examples.length >= props.maxExamples);

const selectedMap = computed(() => {
    const map = new Map<number, SelectedExample>();
    props.selected.forEach((item) => map.set(item.id, item));
    props.available.forEach((item) =>
        map.set(item.id, {
            id: item.id,
            product_name: item.label,
            photo_url: item.photo_url,
        }),
    );
    return map;
});

const addExample = (id: number) => {
    if (isFull.value || form.examples.includes(id)) return;
    form.examples.push(id);
};

const removeExample = (id: number) => {
    form.examples = form.examples.filter((exampleId) => exampleId !== id);
};

const moveUp = (index: number) => {
    if (index === 0) return;
    const items = [...form.examples];
    [items[index - 1], items[index]] = [items[index], items[index - 1]];
    form.examples = items;
};

const moveDown = (index: number) => {
    if (index === form.examples.length - 1) return;
    const items = [...form.examples];
    [items[index + 1], items[index]] = [items[index], items[index + 1]];
    form.examples = items;
};

const submitForm = () => {
    form.post('/landing-page-settings', { preserveScroll: true });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head title="Landing Page" />

        <Breadcrumbs :breadcrumbs="breadcrumbs" />

        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Landing Page</h1>
            <p class="text-sm text-muted-foreground">
                Pilih contoh hasil Virtual Try-On yang tampil di halaman depan website. Hanya produk
                dengan fitur Virtual Try-On (hasil default yang sudah selesai) yang dapat dipilih,
                maksimal {{ props.maxExamples }} contoh.
            </p>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Kolom Kiri: Terpilih -->
                <div class="space-y-3 rounded-xl border bg-card p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-foreground">Contoh Terpilih</h2>
                            <p class="text-xs text-muted-foreground">
                                {{ form.examples.length }} / {{ props.maxExamples }} dipilih
                            </p>
                        </div>
                        <Star class="h-5 w-5 text-amber-500" />
                    </div>

                    <p v-if="form.errors.examples" class="text-xs text-destructive">
                        {{ form.errors.examples }}
                    </p>
                    <p
                        v-for="(error, key) in form.errors"
                        :key="key"
                        v-show="key.startsWith('examples.')"
                        class="text-xs text-destructive"
                    >
                        {{ error }}
                    </p>

                    <div v-if="form.examples.length === 0" class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                        Belum ada contoh dipilih. Tambahkan dari daftar di sebelah kanan.
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="(id, index) in form.examples"
                            :key="id"
                            class="flex items-center gap-3 rounded-lg border bg-background p-3"
                        >
                            <img
                                v-if="selectedMap.get(id)?.photo_url"
                                :src="selectedMap.get(id)?.photo_url || ''"
                                alt=""
                                class="h-14 w-14 rounded-md object-cover"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-foreground">
                                    {{ selectedMap.get(id)?.product_name || 'Produk #' + id }}
                                </p>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ selectedMap.get(id)?.model_name || 'Model default' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <Button type="button" variant="ghost" size="icon" :disabled="index === 0" @click="moveUp(index)">
                                    <ArrowUp class="h-4 w-4" />
                                </Button>
                                <Button type="button" variant="ghost" size="icon" :disabled="index === form.examples.length - 1" @click="moveDown(index)">
                                    <ArrowDown class="h-4 w-4" />
                                </Button>
                                <Button type="button" variant="ghost" size="icon" @click="removeExample(id)">
                                    <Trash2 class="h-4 w-4 text-destructive" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Tersedia -->
                <div class="space-y-3 rounded-xl border bg-card p-5">
                    <div>
                        <h2 class="text-base font-semibold text-foreground">Try-On Default Tersedia</h2>
                        <p class="text-xs text-muted-foreground">
                            Hasil try-on default (tanpa user) berstatus selesai.
                        </p>
                    </div>

                    <div v-if="props.available.length === 0" class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                        Tidak ada try-on default tersedia. Hasilkan try-on default di menu Produk Virtual Try-On terlebih dahulu.
                    </div>

                    <div v-else class="max-h-[520px] space-y-2 overflow-y-auto pr-1">
                        <div
                            v-for="item in props.available"
                            :key="item.id"
                            class="flex items-center gap-3 rounded-lg border bg-background p-2"
                        >
                            <img
                                v-if="item.photo_url"
                                :src="item.photo_url"
                                alt=""
                                class="h-12 w-12 rounded-md object-cover"
                            />
                            <p class="min-w-0 flex-1 truncate text-sm text-foreground">{{ item.label }}</p>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="isFull || form.examples.includes(item.id)"
                                @click="addExample(item.id)"
                            >
                                <Plus class="h-4 w-4" />
                                Tambah
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 rounded-xl border bg-card p-4">
                <Button type="button" variant="outline" :disabled="form.processing" @click="() => form.reset()">
                    Reset
                </Button>
                <Button type="submit" :disabled="form.processing">
                    <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Simpan Perubahan
                </Button>
            </div>
        </form>
    </div>
</template>
