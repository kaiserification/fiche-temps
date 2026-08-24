<script setup lang="ts">
import { cn } from '@/lib/utils';
import { ProgressIndicator, ProgressRoot, type ProgressRootProps } from 'radix-vue';
import type { HTMLAttributes } from 'vue';

interface Props extends ProgressRootProps {
    class?: HTMLAttributes['class'];
    indicatorClass?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: 0,
    max: 100,
});
</script>

<template>
    <ProgressRoot
        :model-value="modelValue"
        :max="max"
        :class="cn('relative h-2 w-full overflow-hidden rounded-full bg-muted', props.class)"
    >
        <ProgressIndicator
            :class="cn('h-full w-full flex-1 rounded-full bg-primary transition-transform duration-300 ease-out', props.indicatorClass)"
            :style="`transform: translateX(-${100 - (100 * (modelValue ?? 0)) / max}%)`"
        />
    </ProgressRoot>
</template>
