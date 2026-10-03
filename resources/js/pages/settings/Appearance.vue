<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
</script>

<template>
    <Head title="Pengaturan Tampilan" />

    <Card class="w-full">
        <CardHeader>
            <CardTitle>Tema & Tampilan</CardTitle>
            <CardDescription>
                Pilih tema tampilan aplikasi yang sesuai dengan preferensi kenyamanan mata Anda.
            </CardDescription>
        </CardHeader>
        <CardContent class="pt-2">
            <AppearanceTabs />
        </CardContent>
    </Card>
</template>
