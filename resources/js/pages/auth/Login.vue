<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Lock, User } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';

defineOptions({
    layout: {
        title: 'Selamat Datang Kembali',
        description: 'Masukkan username dan kata sandi Anda untuk masuk ke sistem.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Masuk" />

    <div
        v-if="status"
        class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50/80 p-3 text-center text-xs font-medium text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <div class="grid gap-4">
            <!-- Username Input -->
            <div class="grid gap-2">
                <Label for="username" class="text-xs font-semibold">Username</Label>
                <div class="relative">
                    <User class="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                    <Input
                        id="username"
                        type="text"
                        name="username"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="username"
                        placeholder="Masukkan username"
                        class="h-9.5 pl-9.5 text-sm"
                    />
                </div>
                <InputError :message="errors.username" />
            </div>

            <!-- Password Input -->
            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-xs font-semibold">Kata Sandi</Label>
                    <TextLink
                        v-if="canResetPassword"
                        href="/forgot-password"
                        class="text-xs text-primary hover:underline"
                    >
                        Lupa kata sandi?
                    </TextLink>
                </div>
                <div class="relative">
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi"
                        class="h-9.5 text-sm"
                    />
                </div>
                <InputError :message="errors.password" />
            </div>

            <!-- Remember me -->
            <div class="flex items-center justify-between pt-1">
                <Label for="remember" class="flex items-center space-x-2.5 cursor-pointer">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span class="text-xs text-muted-foreground select-none">Ingat saya di perangkat ini</span>
                </Label>
            </div>

            <!-- Submit Button -->
            <Button
                type="submit"
                class="mt-2 h-10 w-full font-medium shadow-md shadow-primary/20 transition-all hover:shadow-lg hover:shadow-primary/30"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" class="mr-2" />
                <Lock v-else class="mr-2 h-4 w-4" />
                {{ processing ? 'Memproses Masuk...' : 'Masuk ke Akun' }}
            </Button>
        </div>
    </Form>
</template>
