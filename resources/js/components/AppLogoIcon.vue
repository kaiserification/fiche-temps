<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" :class="className" v-bind="$attrs">
        <path
            fill="currentColor"
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 2.4a7.6 7.6 0 110 15.2 7.6 7.6 0 010-15.2zM11.2 7a1 1 0 012 0v4.586l3.107 3.107a1 1 0 01-1.414 1.414l-3.4-3.4A1 1 0 0111.2 12V7z"
        />
    </svg>
</template>
