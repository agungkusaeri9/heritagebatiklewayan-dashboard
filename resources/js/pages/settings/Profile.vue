<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Mail, Save, User as UserIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Pengaturan Profil" />

    <div class="flex flex-col gap-6">
        <!-- Profile Card -->
        <Card class="w-full">
            <CardHeader>
                <CardTitle>Informasi Profil</CardTitle>
                <CardDescription>
                    Perbarui informasi nama lengkap, username, dan alamat email Anda.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="name">Nama Lengkap</Label>
                            <div class="relative">
                                <UserIcon class="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="name"
                                    class="pl-9.5 text-sm"
                                    name="name"
                                    :default-value="user.name"
                                    required
                                    autocomplete="name"
                                    placeholder="Nama lengkap Anda"
                                />
                            </div>
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="username">Username</Label>
                            <Input
                                id="username"
                                type="text"
                                class="text-sm font-mono"
                                name="username"
                                :default-value="user.username"
                                required
                                autocomplete="username"
                                placeholder="Username Anda"
                            />
                            <InputError :message="errors.username" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Alamat Email</Label>
                            <div class="relative">
                                <Mail class="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="email"
                                    type="email"
                                    class="pl-9.5 text-sm"
                                    name="email"
                                    :default-value="user.email"
                                    autocomplete="email"
                                    placeholder="contoh: user@email.com"
                                />
                            </div>
                            <InputError :message="errors.email" />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <Button
                            type="submit"
                            :disabled="processing"
                            data-test="update-profile-button"
                        >
                            <Save class="mr-2 h-4 w-4" />
                            {{ processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <!-- Delete Account Card -->
        <DeleteUser />
    </div>
</template>
