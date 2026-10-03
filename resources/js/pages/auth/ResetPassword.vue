<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { KeyRound, Mail, ShieldCheck } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Atur Kata Sandi Baru',
        description: 'Silakan tentukan kata sandi baru yang aman untuk akun Anda.',
    },
});

const props = defineProps<{
    token: string;
    email?: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email || '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <Head title="Atur Ulang Kata Sandi" />

    <form @submit.prevent="submit" class="flex flex-col gap-5">
        <div class="grid gap-4">
            <!-- Email Input -->
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

            <!-- New Password Input -->
            <div class="grid gap-2">
                <Label for="password" class="text-xs font-semibold">Kata Sandi Baru</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    v-model="form.password"
                    required
                    :tabindex="2"
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter"
                    class="h-9.5 text-sm"
                />
                <InputError :message="form.errors.password" />
            </div>

            <!-- Confirm Password Input -->
            <div class="grid gap-2">
                <Label for="password_confirmation" class="text-xs font-semibold">Konfirmasi Kata Sandi Baru</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    v-model="form.password_confirmation"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    placeholder="Ulangi kata sandi baru"
                    class="h-9.5 text-sm"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <!-- Submit Button -->
            <Button
                type="submit"
                class="mt-2 h-10 w-full font-medium shadow-md shadow-primary/20 transition-all hover:shadow-lg hover:shadow-primary/30"
                :tabindex="4"
                :disabled="form.processing"
            >
                <Spinner v-if="form.processing" class="mr-2" />
                <ShieldCheck v-else class="mr-2 h-4 w-4" />
                {{ form.processing ? 'Menyimpan Kata Sandi...' : 'Perbarui Kata Sandi' }}
            </Button>
        </div>
    </form>
</template>
