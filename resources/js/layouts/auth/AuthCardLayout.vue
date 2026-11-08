<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-background p-4 sm:p-6 md:p-10"
    >
        <!-- Background Ambient Glow Accents -->
        <div
            class="pointer-events-none absolute -top-32 -left-32 h-80 w-80 rounded-full bg-primary/10 blur-[100px]"
        />
        <div
            class="pointer-events-none absolute -bottom-32 -right-32 h-80 w-80 rounded-full bg-primary/10 blur-[100px]"
        />

        <div class="relative z-10 flex w-full max-w-[420px] flex-col gap-6">
            <!-- Brand Logo Header -->
            <Link
                :href="home()"
                class="flex flex-col items-center gap-2 self-center transition-transform hover:scale-105"
            >
                <img
                    src="/images/logo.png"
                    :alt="$page.props.name || 'Logo'"
                    class="h-14 w-auto object-contain"
                />
            </Link>

            <!-- Elevated Auth Card -->
            <Card class="rounded-2xl border bg-card/95 shadow-xl shadow-black/5 backdrop-blur-xs">
                <CardHeader class="px-6 pt-6 pb-2 text-center sm:px-8">
                    <CardTitle class="text-xl font-bold tracking-tight text-foreground sm:text-2xl">
                        {{ title }}
                    </CardTitle>
                    <CardDescription v-if="description" class="text-sm text-muted-foreground">
                        {{ description }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="px-6 pt-4 pb-6 sm:px-8 sm:pb-8">
                    <slot />
                </CardContent>
            </Card>

            <!-- Footer copyright/info -->
            <div class="text-center text-xs text-muted-foreground">
                &copy; {{ new Date().getFullYear() }} {{ $page.props.name || 'Ecommerce Batik' }}. Hak Cipta Dilindungi.
            </div>
        </div>
    </div>
</template>
