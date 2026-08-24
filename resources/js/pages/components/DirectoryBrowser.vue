<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ChevronRight, Folder, FolderOpen, Loader2, MoveUp } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

const props = defineProps<{ show: boolean; initialPath: string }>();
const emit = defineEmits<{ select: [path: string]; close: [] }>();

const current = ref('');
const parent = ref<string | null>(null);
const dirs = ref<{ name: string; path: string }[]>([]);
const loading = ref(false);
const error = ref('');

async function navigate(path: string) {
    loading.value = true;
    error.value = '';
    try {
        const res = await fetch(`/settings/directories?path=${encodeURIComponent(path)}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        const data = await res.json();
        if (!data.current) {
            error.value = 'Chemin introuvable.';
            return;
        }
        current.value = data.current;
        parent.value = data.parent;
        dirs.value = data.dirs;
    } catch {
        error.value = 'Erreur réseau.';
    } finally {
        loading.value = false;
    }
}

watch(
    () => props.show,
    (val) => {
        if (val) navigate(props.initialPath || '/');
    },
);

onMounted(() => {
    if (props.show) navigate(props.initialPath || '/');
});

function confirm() {
    emit('select', current.value);
}
</script>

<template>
    <Dialog :open="show" @update:open="(v) => !v && emit('close')">
        <DialogContent class="flex max-h-[80vh] flex-col gap-0 p-0 sm:max-w-lg">
            <DialogHeader class="border-b border-border px-4 py-3">
                <div class="flex items-center gap-2">
                    <FolderOpen class="h-4 w-4 text-primary" />
                    <DialogTitle>Sélectionner un dossier</DialogTitle>
                </div>
            </DialogHeader>

            <!-- Current path -->
            <div class="border-b border-border bg-muted/50 px-4 py-2">
                <p class="truncate font-mono text-xs text-muted-foreground" :title="current">{{ current || '…' }}</p>
            </div>

            <!-- Dir list -->
            <div class="flex-1 overflow-y-auto">
                <div v-if="loading" class="flex items-center justify-center py-12">
                    <Loader2 class="h-5 w-5 animate-spin text-primary" />
                </div>

                <p v-else-if="error" class="px-4 py-6 text-center text-xs text-destructive">{{ error }}</p>

                <ul v-else class="divide-y divide-border/60">
                    <!-- Go up -->
                    <li v-if="parent">
                        <button
                            type="button"
                            @click="navigate(parent)"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-muted-foreground transition-colors hover:bg-accent"
                        >
                            <MoveUp class="h-3.5 w-3.5 shrink-0" />
                            <span class="font-mono text-xs">..</span>
                        </button>
                    </li>

                    <li v-if="!dirs.length && !parent">
                        <p class="px-4 py-6 text-center text-xs text-muted-foreground">Aucun sous-dossier</p>
                    </li>

                    <li v-for="dir in dirs" :key="dir.path">
                        <button
                            type="button"
                            @click="navigate(dir.path)"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors hover:bg-primary/5"
                        >
                            <Folder class="h-3.5 w-3.5 shrink-0 text-primary/70" />
                            <span class="flex-1 truncate text-sm text-foreground">{{ dir.name }}</span>
                            <ChevronRight class="h-3.5 w-3.5 shrink-0 text-muted-foreground/50" />
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Footer -->
            <DialogFooter class="border-t border-border px-4 py-3">
                <Button variant="outline" @click="emit('close')">Annuler</Button>
                <Button :disabled="!current" @click="confirm">Sélectionner ce dossier</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
