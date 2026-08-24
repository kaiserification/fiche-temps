<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { AlertTriangle, LoaderCircle } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description?: string;
        confirmLabel?: string;
        cancelLabel?: string;
        loading?: boolean;
        variant?: 'destructive' | 'default';
    }>(),
    {
        confirmLabel: 'Confirmer',
        cancelLabel: 'Annuler',
        loading: false,
        variant: 'destructive',
    },
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();

function onCancel() {
    emit('cancel');
    emit('update:open', false);
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => emit('update:open', v)">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <span
                        v-if="variant === 'destructive'"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-destructive-muted text-destructive"
                    >
                        <AlertTriangle class="h-5 w-5" />
                    </span>
                    <DialogTitle>{{ title }}</DialogTitle>
                </div>
                <DialogDescription v-if="description" class="pt-1 text-left">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" type="button" :disabled="loading" @click="onCancel">
                    {{ cancelLabel }}
                </Button>
                <Button :variant="variant === 'destructive' ? 'destructive' : 'default'" type="button" :disabled="loading" @click="emit('confirm')">
                    <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
