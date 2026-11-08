<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

const page = usePage<any>();
</script>

<template>
    <img
        src="/images/logo.png"
        :alt="page.props.name || 'Logo'"
        class="h-8 w-auto max-w-full shrink-0 object-contain"
    />
    <div class="grid min-w-0 flex-1 text-left text-sm group-data-[collapsible=icon]:hidden">
        <span class="mb-0.5 truncate leading-tight font-semibold">
            {{ page.props.name }}
        </span>
    </div>
</template>
