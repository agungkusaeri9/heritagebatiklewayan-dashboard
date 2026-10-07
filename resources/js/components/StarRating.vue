<script setup lang="ts">
import { computed } from 'vue';
import { Star } from 'lucide-vue-next';

interface Props {
    rating: number;
    maxStars?: number;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    showScore?: boolean;
    scoreClass?: string;
    showCount?: boolean;
    count?: number;
}

const props = withDefaults(defineProps<Props>(), {
    maxStars: 5,
    size: 'md',
    showScore: false,
    showCount: false,
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs':
            return {
                star: 'h-3.5 w-3.5',
                gap: 'gap-0.5',
                text: 'text-xs',
            };
        case 'sm':
            return {
                star: 'h-4 w-4',
                gap: 'gap-1',
                text: 'text-sm',
            };
        case 'lg':
            return {
                star: 'h-6 w-6',
                gap: 'gap-1.5',
                text: 'text-lg',
            };
        case 'xl':
            return {
                star: 'h-8 w-8',
                gap: 'gap-2',
                text: 'text-2xl font-bold',
            };
        case 'md':
        default:
            return {
                star: 'h-5 w-5',
                gap: 'gap-1',
                text: 'text-base font-semibold',
            };
    }
});

const stars = computed(() => {
    const list = [];
    for (let i = 1; i <= props.maxStars; i++) {
        if (props.rating >= i) {
            list.push({ index: i, fillPercent: 100 });
        } else if (props.rating > i - 1) {
            const fraction = props.rating - (i - 1);
            list.push({ index: i, fillPercent: Math.min(100, Math.max(0, Math.round(fraction * 100))) });
        } else {
            list.push({ index: i, fillPercent: 0 });
        }
    }
    return list;
});

const formattedRating = computed(() => {
    return Number(props.rating).toFixed(1);
});
</script>

<template>
    <div class="inline-flex items-center" :class="sizeClasses.gap">
        <div class="flex items-center" :class="sizeClasses.gap">
            <div
                v-for="star in stars"
                :key="star.index"
                class="relative inline-flex items-center justify-center shrink-0"
                :class="sizeClasses.star"
            >
                <!-- Base / Unfilled Star (Gray) -->
                <Star
                    :class="[sizeClasses.star, 'text-muted-foreground/30 fill-muted/40 stroke-1']"
                    aria-hidden="true"
                />

                <!-- Filled Star (Yellow / Amber) with precise fractional width overlay -->
                <div
                    v-if="star.fillPercent > 0"
                    class="absolute top-0 left-0 bottom-0 overflow-hidden pointer-events-none"
                    :style="{ width: `${star.fillPercent}%` }"
                >
                    <Star
                        :class="[sizeClasses.star, 'text-amber-400 fill-amber-400 stroke-amber-500 stroke-1']"
                        aria-hidden="true"
                    />
                </div>
            </div>
        </div>

        <!-- Rating Score Value (e.g. 4.6) -->
        <span
            v-if="showScore"
            :class="[sizeClasses.text, scoreClass || 'text-foreground font-semibold ml-1.5']"
        >
            {{ formattedRating }}
        </span>

        <!-- Review Count (e.g. (14)) -->
        <span
            v-if="showCount && count !== undefined"
            class="text-xs text-muted-foreground ml-1"
        >
            ({{ count }})
        </span>
    </div>
</template>
