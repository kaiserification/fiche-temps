<script setup lang="ts">
import { ChevronRight, Folder, FolderOpen, Loader2, MoveUp, X } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

const props = defineProps<{ show: boolean; initialPath: string }>();
const emit  = defineEmits<{ select: [path: string]; close: [] }>();

const current  = ref('');
const parent   = ref<string | null>(null);
const dirs     = ref<{ name: string; path: string }[]>([]);
const loading  = ref(false);
const error    = ref('');

async function navigate(path: string) {
    loading.value = true;
    error.value   = '';
    try {
        const res  = await fetch(`/settings/directories?path=${encodeURIComponent(path)}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        const data = await res.json();
        if (!data.current) {
            error.value = 'Chemin introuvable.';
            return;
        }
        current.value = data.current;
        parent.value  = data.parent;
        dirs.value    = data.dirs;
    } catch {
        error.value = 'Erreur réseau.';
    } finally {
        loading.value = false;
    }
}

watch(() => props.show, (val) => {
    if (val) navigate(props.initialPath || '/');
});

onMounted(() => {
    if (props.show) navigate(props.initialPath || '/');
});

function confirm() {
    emit('select', current.value);
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-150"
            leave-active-class="transition-opacity duration-150"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
                @click.self="emit('close')"
            >
                <div class="flex w-full max-w-lg flex-col rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800"
                     style="max-height: 80vh">

                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <FolderOpen class="h-4 w-4 text-indigo-500" />
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Sélectionner un dossier</span>
                        </div>
                        <button @click="emit('close')" class="rounded p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Current path -->
                    <div class="border-b border-gray-100 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-700/50">
                        <p class="truncate font-mono text-xs text-gray-600 dark:text-gray-400" :title="current">
                            {{ current || '…' }}
                        </p>
                    </div>

                    <!-- Dir list -->
                    <div class="flex-1 overflow-y-auto">
                        <div v-if="loading" class="flex items-center justify-center py-12">
                            <Loader2 class="h-5 w-5 animate-spin text-indigo-400" />
                        </div>

                        <p v-else-if="error" class="px-4 py-6 text-center text-xs text-red-500">{{ error }}</p>

                        <ul v-else class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            <!-- Go up -->
                            <li v-if="parent">
                                <button
                                    @click="navigate(parent)"
                                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm text-gray-500 transition-colors hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700/50"
                                >
                                    <MoveUp class="h-3.5 w-3.5 shrink-0" />
                                    <span class="font-mono text-xs">..</span>
                                </button>
                            </li>

                            <li v-if="!dirs.length && !parent">
                                <p class="px-4 py-6 text-center text-xs text-gray-400">Aucun sous-dossier</p>
                            </li>

                            <li v-for="dir in dirs" :key="dir.path">
                                <button
                                    @click="navigate(dir.path)"
                                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition-colors hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
                                >
                                    <Folder class="h-3.5 w-3.5 shrink-0 text-indigo-400" />
                                    <span class="flex-1 truncate text-sm text-gray-800 dark:text-gray-200">{{ dir.name }}</span>
                                    <ChevronRight class="h-3.5 w-3.5 shrink-0 text-gray-300 dark:text-gray-600" />
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-4 py-3 dark:border-gray-700">
                        <button
                            @click="emit('close')"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700"
                        >
                            Annuler
                        </button>
                        <button
                            @click="confirm"
                            :disabled="!current"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Sélectionner ce dossier
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
