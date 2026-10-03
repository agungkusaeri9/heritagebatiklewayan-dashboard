<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Mail, Send } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Pemulihan Kata Sandi',
        description:
            'Masukkan alamat email yang terdaftar. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi Anda.',
    },
});

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <Head title="Lupa Kata Sandi" />

    <div
        v-if="status"
        class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50/80 p-3.5 text-center text-xs font-medium text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300"
    >
        {{ status }}
    </div>

    <form @submit.prevent="submit" class="flex flex-col gap-5">
        <div class="grid gap-4">
            <div class="grid gap-2">
                <Label for="email" class="text-xs font-semibold">Alamat Email</Label>
                <div class="relative">
                    <Mail class="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        v-model="form.email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="nama@email.com"
                        class="h-9.5 pl-9.5 text-sm"
                    />
                </div>
                <InputError :message="form.errors.email" />
            </div>

            <Button
                type="submit"
                class="mt-2 h-10 w-full font-medium shadow-md shadow-primary/20 transition-all hover:shadow-lg hover:shadow-primary/30"
                :tabindex="2"
                :disabled="form.processing"
            >
                <Spinner v-if="form.processing" class="mr-2" />
                <Send v-else class="mr-2 h-4 w-4" />
                {{ form.processing ? 'Mengirim Tautan...' : 'Kirim Tautan Pemulihan' }}
            </Button>
        </div>

        <div class="mt-2 text-center text-xs text-muted-foreground">
            <TextLink href="/login" class="inline-flex items-center gap-1.5 font-medium text-primary hover:underline">
                <ArrowLeft class="h-3.5 w-3.5" />
                Kembali ke halaman masuk
            </TextLink>
        </div>
    </form>
</template>
