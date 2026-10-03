<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { AlertTriangle, Trash2 } from 'lucide-vue-next';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <Card class="w-full border-destructive/20 bg-destructive/5">
        <CardHeader>
            <div class="flex items-center gap-2 text-destructive">
                <AlertTriangle class="h-5 w-5" />
                <CardTitle class="text-destructive">Hapus Akun</CardTitle>
            </div>
            <CardDescription class="text-destructive/80">
                Tindakan ini akan menghapus akun Anda beserta seluruh data terkait secara permanen.
            </CardDescription>
        </CardHeader>
        <CardContent>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive" data-test="delete-user-button">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Hapus Akun Saya
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-[425px]">
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-2">
                            <div class="flex items-center gap-2 text-destructive">
                                <AlertTriangle class="h-5 w-5" />
                                <DialogTitle>Konfirmasi Hapus Akun</DialogTitle>
                            </div>
                            <DialogDescription>
                                Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen.
                                Masukkan kata sandi Anda untuk mengonfirmasi penghapusan akun.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="text-xs font-semibold">Kata Sandi Anda</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                ref="passwordInput"
                                placeholder="Masukkan kata sandi untuk konfirmasi"
                                class="text-sm"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-3 sm:gap-3">
                            <DialogClose as-child>
                                <Button
                                    variant="outline"
                                    type="button"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    Batal
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                {{ processing ? 'Menghapus...' : 'Ya, Hapus Akun' }}
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </CardContent>
    </Card>
</template>
