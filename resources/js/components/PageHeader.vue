<script setup lang="ts">
withDefaults(defineProps<{ title?: string; description?: string }>(), {
    title: '',
    description: '',
});
</script>

<template>
    <div class="flex flex-1 flex-wrap items-center justify-between gap-3">
        <div v-if="title || $slots.title" class="min-w-0">
            <h1 class="truncate text-base font-semibold text-foreground">
                <slot name="title">{{ title }}</slot>
            </h1>
            <p v-if="description || $slots.description" class="truncate text-sm text-muted-foreground">
                <slot name="description">{{ description }}</slot>
            </p>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
