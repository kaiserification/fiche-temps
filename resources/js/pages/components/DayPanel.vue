<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import { Check, Copy, FileSpreadsheet, GitBranch, LoaderCircle, List, X } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

dayjs.locale('fr');

const props = defineProps<{
    day: string;
    entryId?: number | null;
    tasks: string[];
    comment?: string;
    projet?: string;
    ficheProjet?: string;
    saving?: boolean;
    ficheExists?: boolean;
}>();
const emit = defineEmits<{ (e: 'save', day: string, entryId: number | null | undefined, tasks: string[], comment: string, projet: string): void }>();

const localTasks = ref<string[]>([...props.tasks]);
const localComment = ref(props.comment ?? '');
const localProjet = ref(props.projet ?? '');

watch(
    () => props.tasks,
    (t) => {
        localTasks.value = [...t];
    },
);
watch(
    () => props.comment,
    (c) => {
        localComment.value = c ?? '';
    },
);
watch(
    () => props.projet,
    (p) => {
        localProjet.value = p ?? '';
    },
);
watch(
    () => props.day,
    () => {
        localTasks.value = [...props.tasks];
        localComment.value = props.comment ?? '';
        localProjet.value = props.projet ?? '';
    },
);

const dateLabel = () => dayjs(props.day).format('dddd D MMMM');

async function addTask(atStart = false) {
    if (atStart) {
        localTasks.value.unshift('');
    } else {
        localTasks.value.push('');
    }
    await nextTick();
    const items = document.querySelectorAll<HTMLInputElement>('.task-item');
    const target = atStart ? items[0] : items[items.length - 1];
    target?.focus();
}

function removeTask(i: number) {
    localTasks.value.splice(i, 1);
}

function onSave() {
    emit(
        'save',
        props.day,
        props.entryId,
        localTasks.value.filter((t) => t.trim()),
        localComment.value.trim(),
        localProjet.value.trim(),
    );
    toast.success('Fiche sauvegardée avec succès !');
}

// ── Vue Excel ─────────────────────────────────────────────────────────────────
const showExcel = ref(false);
const excelCopied = ref(false);

const excelText = computed(() =>
    localTasks.value
        .filter((t) => t.trim())
        .map((t, i) => `${i + 1}. ${t.trim()}`)
        .join('\n'),
);

async function copyForExcel() {
    if (!excelText.value) return;
    await navigator.clipboard.writeText(excelText.value);
    excelCopied.value = true;
    setTimeout(() => (excelCopied.value = false), 2000);
}

// ── Saisie rapide (bulk) ──────────────────────────────────────────────────────
const showBulk = ref(false);
const bulkText = ref('');

function openBulk() {
    bulkText.value = localTasks.value
        .filter((t) => t.trim())
        .map((t, i) => `${i + 1}. ${t.trim()}`)
        .join('\n');
    showBulk.value = !showBulk.value;
}

function importBulk() {
    const lines = parseTaskLines(bulkText.value);
    if (!lines.length) return;
    localTasks.value = lines;
    showBulk.value = false;
    toast.success(`${lines.length} tâche${lines.length > 1 ? 's' : ''} importée${lines.length > 1 ? 's' : ''}`);
}

// ── Git → inject ──────────────────────────────────────────────────────────────
const gitOpen = ref(false);
const gitProjects = ref<string[]>([]);
const gitProject = ref('');
const gitLoading = ref(false);
const gitError = ref('');

function getCsrf() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function toggleGit() {
    gitError.value = '';
    gitOpen.value = !gitOpen.value;
    if (gitOpen.value && !gitProjects.value.length) {
        try {
            const res = await fetch('/git-projects', { credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const data = await res.json();
            gitProjects.value = data.projects ?? [];
            gitProject.value = gitProjects.value[0] ?? '';
        } catch {
            /* silent */
        }
    }
}

function parseTaskLines(text: string) {
    return text
        .split('\n')
        .filter((line) => /^\d+[.)]\s/.test(line.trim()))
        .map((line) => line.replace(/^\d+[.)]\s+/, '').trim())
        .filter(Boolean);
}

async function generateFromGit() {
    if (!gitProject.value) return;
    gitError.value = '';
    gitLoading.value = true;
    try {
        const res = await fetch('/git-to-timesheet', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': getCsrf() },
            body: JSON.stringify({ date: props.day, project: gitProject.value }),
        });
        const data = await res.json();
        if (!res.ok) {
            gitError.value = data.error ?? 'Une erreur est survenue.';
        } else if (data.empty) {
            gitError.value = 'Aucun commit trouvé pour ce jour.';
        } else {
            const lines = parseTaskLines(data.tasks ?? '');
            if (lines.length) {
                localTasks.value = lines;
                gitOpen.value = false;
                toast.success(`${lines.length} tâche${lines.length > 1 ? 's' : ''} injectée${lines.length > 1 ? 's' : ''} depuis Git`);
            } else {
                gitError.value = 'Aucune tâche extraite de la réponse.';
            }
        }
    } catch {
        gitError.value = 'Erreur réseau.';
    } finally {
        gitLoading.value = false;
    }
}
</script>

<template>
    <div class="sticky top-20 overflow-hidden rounded-xl border border-border bg-card shadow-soft-sm">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-border bg-muted/50 px-4 py-3">
            <h3 class="text-sm font-semibold capitalize text-foreground">{{ dateLabel() }}</h3>
            <span v-if="entryId" class="flex items-center gap-1 text-[10px] font-medium text-success">
                <Check class="h-3 w-3" />
                Enregistré
            </span>
        </div>

        <div class="space-y-4 p-4">
            <!-- Project override -->
            <div>
                <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Projet</p>
                <Input
                    v-model="localProjet"
                    type="text"
                    :placeholder="ficheProjet || 'Nom du projet'"
                    title="Laissez vide pour utiliser le projet par défaut de la fiche"
                    class="h-9 text-sm"
                />
            </div>

            <!-- Task list -->
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                        Tâches
                        <span class="font-normal text-muted-foreground/60">({{ localTasks.length }})</span>
                    </p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="toggleGit"
                            :class="gitOpen ? 'text-primary' : 'text-muted-foreground hover:text-primary'"
                            class="flex items-center gap-1 text-[10px] font-medium transition-colors"
                            title="Générer depuis Git"
                        >
                            <GitBranch class="h-3 w-3" />
                            Git
                        </button>
                        <span class="text-border">|</span>
                        <button
                            type="button"
                            @click="openBulk"
                            :class="showBulk ? 'text-info' : 'text-muted-foreground hover:text-info'"
                            class="flex items-center gap-1 text-[10px] font-medium transition-colors"
                            title="Saisie rapide (liste numérotée)"
                        >
                            <List class="h-3 w-3" />
                            Liste
                        </button>
                        <span class="text-border">|</span>
                        <button
                            type="button"
                            @click="showExcel = !showExcel"
                            :class="showExcel ? 'text-success' : 'text-muted-foreground hover:text-success'"
                            class="flex items-center gap-1 text-[10px] font-medium transition-colors"
                            title="Vue cellule Excel"
                        >
                            <FileSpreadsheet class="h-3 w-3" />
                            Excel
                        </button>
                        <span class="text-border">|</span>
                        <button
                            type="button"
                            @click="addTask(true)"
                            title="Ajouter une tâche en début de liste"
                            class="text-[10px] font-medium text-primary transition-colors hover:text-primary/80"
                        >
                            + Début
                        </button>
                        <span class="text-border">|</span>
                        <button
                            type="button"
                            @click="addTask()"
                            title="Ajouter une tâche en fin de liste"
                            class="text-[10px] font-medium text-primary transition-colors hover:text-primary/80"
                        >
                            + Ajouter
                        </button>
                    </div>
                </div>

                <!-- Git generate panel -->
                <div v-if="gitOpen" class="mb-3 space-y-2 rounded-lg border border-primary/20 bg-primary/5 p-3">
                    <select
                        v-model="gitProject"
                        class="w-full rounded-md border border-primary/30 bg-background px-2.5 py-1.5 text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
                    >
                        <option v-if="!gitProjects.length" value="" disabled>Chargement…</option>
                        <option v-for="p in gitProjects" :key="p" :value="p">{{ p }}</option>
                    </select>
                    <Button size="sm" class="w-full" :disabled="gitLoading || !gitProject" @click="generateFromGit">
                        <LoaderCircle v-if="gitLoading" class="h-3 w-3 animate-spin" />
                        <GitBranch v-else class="h-3 w-3" />
                        {{ gitLoading ? 'Génération…' : 'Générer les tâches' }}
                    </Button>
                    <p v-if="gitError" class="text-[10px] text-destructive">{{ gitError }}</p>
                </div>

                <!-- Bulk input panel -->
                <div v-if="showBulk" class="mb-3 space-y-2 rounded-lg border border-info/20 bg-info/5 p-3">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wider text-info">
                            <List class="h-3 w-3" />
                            Saisie rapide
                        </span>
                        <span class="text-[10px] text-info/70">Format : 1. tâche, 2. tâche…</span>
                    </div>
                    <Textarea
                        v-model="bulkText"
                        :rows="Math.max(4, bulkText.split('\n').length + 1)"
                        placeholder="1. Première tâche&#10;2. Deuxième tâche&#10;3. Troisième tâche"
                        class="resize-none font-mono text-xs"
                    />
                    <Button size="sm" class="w-full bg-info text-info-foreground hover:bg-info/90" :disabled="!bulkText.trim()" @click="importBulk">
                        <List class="h-3 w-3" />
                        Importer les tâches
                    </Button>
                </div>

                <!-- Excel cell view -->
                <div v-if="showExcel && localTasks.filter((t) => t.trim()).length" class="mb-3 space-y-2 rounded-lg border border-success/20 bg-success-muted p-3">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wider text-success">
                            <FileSpreadsheet class="h-3 w-3" />
                            Cellule Excel
                        </span>
                        <button
                            type="button"
                            @click="copyForExcel"
                            class="flex items-center gap-1 rounded px-2 py-0.5 text-[10px] font-medium transition-colors"
                            :class="excelCopied ? 'bg-success/20 text-success' : 'text-success hover:bg-success/10'"
                        >
                            <Check v-if="excelCopied" class="h-3 w-3" />
                            <Copy v-else class="h-3 w-3" />
                            {{ excelCopied ? 'Copié !' : 'Copier' }}
                        </button>
                    </div>
                    <textarea
                        readonly
                        :value="excelText"
                        :rows="localTasks.filter((t) => t.trim()).length + 1"
                        class="w-full resize-none select-all rounded-md border border-success/30 bg-background px-2.5 py-2 font-mono text-xs text-foreground focus:outline-none focus:ring-2 focus:ring-success/40"
                        @click="($event.target as HTMLTextAreaElement).select()"
                    />
                    <p class="text-[10px] text-success/70">Double-cliquez dans une cellule Excel, puis collez (Ctrl+V).</p>
                </div>

                <div v-if="localTasks.length" class="space-y-1.5">
                    <div v-for="(_, i) in localTasks" :key="i" class="flex items-center gap-2">
                        <span class="w-4 shrink-0 text-right text-[11px] tabular-nums text-muted-foreground/50">{{ i + 1 }}.</span>
                        <Input v-model="localTasks[i]" type="text" placeholder="Décrivez la tâche…" class="task-item h-9 flex-1 text-sm" />
                        <button
                            type="button"
                            @click="removeTask(i)"
                            class="shrink-0 text-muted-foreground/50 transition-colors hover:text-destructive"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div v-else class="py-4 text-center">
                    <p class="mb-3 text-xs text-muted-foreground">Aucune tâche pour ce jour</p>
                    <Button variant="outline" size="sm" @click="addTask()">+ Ajouter une tâche</Button>
                </div>
            </div>

            <!-- Comment -->
            <div>
                <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Commentaire</p>
                <Textarea v-model="localComment" rows="3" placeholder="Observations, blocages, notes…" class="resize-none text-sm" />
            </div>

            <!-- Save -->
            <template v-if="ficheExists">
                <Button class="w-full" :disabled="saving" @click="onSave">
                    <LoaderCircle v-if="saving" class="h-4 w-4 animate-spin" />
                    {{ saving ? 'Sauvegarde…' : 'Sauvegarder' }}
                </Button>
            </template>
            <p v-else class="text-center text-[10px] text-warning">Créez d'abord la fiche pour sauvegarder</p>
        </div>
    </div>
</template>
