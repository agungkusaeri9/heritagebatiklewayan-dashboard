<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { KeyRound, ShieldCheck } from 'lucide-vue-next';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';

type Props = {
    passwordRules: string;
};

const props = defineProps<Props>();
</script>

<template>
    <Head title="Pengaturan Keamanan" />

    <Card class="w-full">
        <CardHeader>
            <CardTitle>Perbarui Kata Sandi</CardTitle>
            <CardDescription>
                Pastikan akun Anda menggunakan kata sandi yang panjang dan acak untuk menjaga keamanan akun.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <Form
                v-bind="SecurityController.update.form()"
                :options="{
                    preserveScroll: true,
                }"
                reset-on-success
                :reset-on-error="[
                    'password',
                    'password_confirmation',
                    'current_password',
                ]"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="current_password">Kata Sandi Saat Ini</Label>
                        <PasswordInput
                            id="current_password"
                            name="current_password"
                            class="text-sm"
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi saat ini"
                        />
                        <InputError :message="errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Kata Sandi Baru</Label>
                        <PasswordInput
                            id="password"
                            name="password"
                            class="text-sm"
                            autocomplete="new-password"
                            placeholder="Minimal 8 karakter"
                            :passwordrules="props.passwordRules"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">Konfirmasi Kata Sandi Baru</Label>
                        <PasswordInput
                            id="password_confirmation"
                            name="password_confirmation"
                            class="text-sm"
                            autocomplete="new-password"
                            placeholder="Ulangi kata sandi baru"
                            :passwordrules="props.passwordRules"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="update-password-button"
                    >
                        <ShieldCheck class="mr-2 h-4 w-4" />
                        {{ processing ? 'Menyimpan...' : 'Perbarui Kata Sandi' }}
                    </Button>
                </div>
            </Form>
        </CardContent>
    </Card>
</template>
