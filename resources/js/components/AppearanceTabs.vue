<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Mode Terang' },
    { value: 'dark', Icon: Moon, label: 'Mode Gelap' },
    { value: 'system', Icon: Monitor, label: 'Ikuti Sistem' },
] as const;
</script>

<template>
    <div
        class="inline-flex gap-1.5 rounded-xl border bg-muted/40 p-1.5 shadow-2xs"
    >
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            type="button"
            @click="updateAppearance(value)"
            :class="[
                'flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-medium transition-all',
                appearance === value
                    ? 'bg-card text-foreground shadow-xs font-semibold'
                    : 'text-muted-foreground hover:bg-muted/70 hover:text-foreground',
            ]"
        >
            <component :is="Icon" class="h-4 w-4 shrink-0" />
            <span>{{ label }}</span>
        </button>
    </div>
</template>
