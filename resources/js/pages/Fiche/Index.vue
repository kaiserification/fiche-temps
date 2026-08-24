<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import { Check, Copy, FileText, GitBranch, LoaderCircle, Plus, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';

dayjs.locale('fr');

const props = defineProps<{
    fiches: Array<{
        id: number;
        projet: string | null;
        business_unit: string | null;
        period_start: string;
        period_end: string;
        day_entries_count: number;
    }>;
    periodStart?: string;
    periodEnd?: string;
}>();

const confirmingDelete = ref<number | null>(null);
const deleteLoading = ref(false);

// ── Création modal ───────────────────────────────────────────────────────────
const createModalOpen = ref(false);
const createProjet = ref('');
const createBu = ref('');
const createStart = ref(props.periodStart ?? dayjs().format('YYYY-MM-DD'));
const createEnd = ref(props.periodEnd ?? dayjs().format('YYYY-MM-DD'));
const createError = ref('');
const createLoading = ref(false);

function openCreateModal() {
    createProjet.value = '';
    createBu.value = '';
    createStart.value = props.periodStart ?? dayjs().format('YYYY-MM-DD');
    createEnd.value = props.periodEnd ?? dayjs().format('YYYY-MM-DD');
    createError.value = '';
    createModalOpen.value = true;
}

function closeCreateModal() {
    createModalOpen.value = false;
}

function submitCreateFiche() {
    createError.value = '';
    if (!createProjet.value.trim()) {
        createError.value = 'Le nom du projet est requis.';
        return;
    }
    if (dayjs(createEnd.value).isBefore(dayjs(createStart.value))) {
        createError.value = 'La date de fin doit être après la date de début.';
        return;
    }
    createLoading.value = true;
    router.post(
        '/fiche',
        {
            projet: createProjet.value,
            business_unit: createBu.value,
            period_start: createStart.value,
            period_end: createEnd.value,
        },
        {
            onError: () => {
                createError.value = 'Une erreur est survenue.';
                createLoading.value = false;
            },
        },
    );
}

// ── Git → Fiche ──────────────────────────────────────────────────────────────
const gitModalOpen = ref(false);
const gitDate = ref(dayjs().format('YYYY-MM-DD'));
const gitLoading = ref(false);
const gitError = ref('');
const gitResult = ref('');
const gitEmpty = ref(false);
const copied = ref(false);
const gitProjects = ref<string[]>([]);
const gitProject = ref('');

async function loadProjects() {
    try {
        const res = await fetch('/git-projects', { credentials: 'same-origin', headers: { Accept: 'application/json' } });
        const data = await res.json();
        gitProjects.value = data.projects ?? [];
        const current = window.location.hostname.split('.')[0];
        gitProject.value = gitProjects.value.includes(current) ? current : (gitProjects.value[0] ?? '');
    } catch {
        gitProjects.value = [];
    }
}

async function openGitModal() {
    gitDate.value = dayjs().format('YYYY-MM-DD');
    gitResult.value = '';
    gitError.value = '';
    gitEmpty.value = false;
    copied.value = false;
    gitModalOpen.value = true;
    await loadProjects();
}

function getCsrf() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function generateFromGit() {
    gitError.value = '';
    gitResult.value = '';
    gitEmpty.value = false;
    gitLoading.value = true;
    try {
        const res = await fetch('/git-to-timesheet', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify({ date: gitDate.value, project: gitProject.value }),
        });
        const data = await res.json();
        if (!res.ok) {
            gitError.value = data.error ?? 'Une erreur est survenue.';
        } else if (data.empty) {
            gitEmpty.value = true;
        } else {
            gitResult.value = data.tasks ?? '';
        }
    } catch {
        gitError.value = 'Erreur réseau. Vérifiez votre connexion.';
    } finally {
        gitLoading.value = false;
    }
}

async function copyResult() {
    if (!gitResult.value) return;
    await navigator.clipboard.writeText(gitResult.value);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}

function deleteFiche() {
    if (confirmingDelete.value === null) return;
    deleteLoading.value = true;
    router.delete(`/fiche/${confirmingDelete.value}`, {
        onFinish: () => {
            deleteLoading.value = false;
            confirmingDelete.value = null;
        },
    });
}

function formatPeriod(start: string, end: string) {
    return dayjs(start).format('D MMM') + ' → ' + dayjs(end).format('D MMM YYYY');
}

function progressPct(count: number) {
    return Math.min(100, Math.round((count / 22) * 100));
}

function statusLabel(count: number) {
    if (count === 0) return 'Non commencée';
    if (count >= 20) return 'Complète';
    return 'En cours';
}

function statusVariant(count: number): 'muted' | 'success' | 'default' {
    if (count === 0) return 'muted';
    if (count >= 20) return 'success';
    return 'default';
}
</script>

<template>
    <Head title="Fiches de temps" />

    <AppLayout>
        <template #header>
            <PageHeader title="Fiches de temps" :description="`${fiches.length} période${fiches.length !== 1 ? 's' : ''}`">
                <template #actions>
                    <Button variant="outline" @click="openGitModal">
                        <GitBranch class="h-4 w-4" />
                        Git → Fiche
                    </Button>
                    <Button @click="openCreateModal">
                        <Plus class="h-4 w-4" />
                        Nouvelle fiche
                    </Button>
                </template>
            </PageHeader>
        </template>

        <div class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6">
            <!-- Empty state -->
            <div v-if="!fiches.length" class="py-24 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                    <FileText class="h-7 w-7 text-muted-foreground" />
                </div>
                <h2 class="mb-1 text-sm font-semibold text-foreground">Aucune fiche</h2>
                <p class="mb-5 text-sm text-muted-foreground">Commencez par créer votre première fiche de temps.</p>
                <Button @click="openCreateModal">Créer une fiche</Button>
            </div>

            <!-- Grid -->
            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="fiche in fiches"
                    :key="fiche.id"
                    @click="router.visit(`/fiche/${fiche.id}`)"
                    class="group cursor-pointer rounded-xl border border-border bg-card p-5 shadow-soft-sm transition-all duration-150 hover:border-primary/40 hover:shadow-soft-md"
                >
                    <!-- Header -->
                    <div class="mb-3 flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="mb-1 text-[11px] font-medium tracking-wide text-muted-foreground">
                                {{ formatPeriod(fiche.period_start, fiche.period_end) }}
                            </p>
                            <h2 class="truncate text-sm font-semibold text-foreground transition-colors group-hover:text-primary">
                                {{ fiche.projet || 'Projet non renseigné' }}
                            </h2>
                            <p class="mt-0.5 truncate text-xs text-muted-foreground">
                                {{ fiche.business_unit || '—' }}
                            </p>
                        </div>
                        <Badge class="ml-3 shrink-0" :variant="statusVariant(fiche.day_entries_count)">
                            {{ statusLabel(fiche.day_entries_count) }}
                        </Badge>
                    </div>

                    <!-- Progress -->
                    <div class="mb-4">
                        <div class="mb-1.5 flex items-center justify-between">
                            <span class="text-[10px] text-muted-foreground">Progression</span>
                            <span class="text-[10px] font-medium tabular-nums text-muted-foreground">{{ fiche.day_entries_count }} / 22 jours</span>
                        </div>
                        <Progress :model-value="progressPct(fiche.day_entries_count)" class="h-1.5" />
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <Link
                            :href="`/fiche/${fiche.id}`"
                            prefetch
                            class="flex-1 rounded-lg border border-border py-1.5 text-center text-xs font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                        >
                            Ouvrir
                        </Link>
                        <a
                            :href="`/fiche/${fiche.id}/export`"
                            @click.stop
                            class="flex-1 rounded-lg border border-success/30 py-1.5 text-center text-xs font-medium text-success transition-colors hover:bg-success-muted"
                        >
                            .xlsx
                        </a>
                        <a
                            :href="`/fiche/${fiche.id}/export/pdf`"
                            @click.stop
                            class="flex-1 rounded-lg border border-destructive/30 py-1.5 text-center text-xs font-medium text-destructive transition-colors hover:bg-destructive-muted"
                        >
                            .pdf
                        </a>
                        <button
                            type="button"
                            @click.stop="confirmingDelete = fiche.id"
                            class="rounded-lg p-1.5 text-muted-foreground/60 transition-colors hover:bg-destructive-muted hover:text-destructive"
                            title="Supprimer"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmDialog
            :open="confirmingDelete !== null"
            title="Supprimer cette fiche ?"
            description="Cette action est irréversible : toutes les journées saisies seront perdues."
            confirm-label="Supprimer"
            :loading="deleteLoading"
            @update:open="(v) => !v && (confirmingDelete = null)"
            @confirm="deleteFiche"
        />

        <!-- ── Git → Fiche modal ──────────────────────────────────────────────── -->
        <Dialog :open="gitModalOpen" @update:open="(v) => (gitModalOpen = v)">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10">
                            <GitBranch class="h-4 w-4 text-primary" />
                        </div>
                        <DialogTitle>Git → Fiche de temps</DialogTitle>
                    </div>
                </DialogHeader>

                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <Label>Projet</Label>
                        <select
                            v-model="gitProject"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option v-if="!gitProjects.length" value="" disabled>Chargement…</option>
                            <option v-for="p in gitProjects" :key="p" :value="p">{{ p }}</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label>Date</Label>
                        <Input v-model="gitDate" type="date" />
                    </div>

                    <Button :disabled="gitLoading" @click="generateFromGit">
                        <LoaderCircle v-if="gitLoading" class="h-4 w-4 animate-spin" />
                        <GitBranch v-else class="h-4 w-4" />
                        {{ gitLoading ? 'Génération en cours…' : 'Générer les tâches' }}
                    </Button>

                    <p v-if="gitError" class="rounded-lg bg-destructive-muted px-3 py-2 text-sm text-destructive">{{ gitError }}</p>

                    <p v-if="gitEmpty" class="rounded-lg bg-muted px-3 py-2 text-sm text-muted-foreground">
                        Aucun commit trouvé pour le {{ dayjs(gitDate).locale('fr').format('D MMMM YYYY') }}.
                    </p>

                    <div v-if="gitResult" class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium text-muted-foreground">Tâches générées</span>
                            <Button variant="ghost" size="sm" @click="copyResult">
                                <Check v-if="copied" class="h-3.5 w-3.5" />
                                <Copy v-else class="h-3.5 w-3.5" />
                                {{ copied ? 'Copié !' : 'Copier' }}
                            </Button>
                        </div>
                        <pre class="max-h-64 overflow-y-auto whitespace-pre-wrap rounded-lg border border-border bg-muted px-4 py-3 text-sm leading-relaxed text-foreground">{{
                            gitResult
                        }}</pre>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- ── Création fiche modal ─────────────────────────────────────────────── -->
        <Dialog :open="createModalOpen" @update:open="(v) => (createModalOpen = v)">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Nouvelle fiche de temps</DialogTitle>
                </DialogHeader>

                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <Label>Projet <span class="text-destructive">*</span></Label>
                        <Input v-model="createProjet" type="text" placeholder="Nom du projet" autofocus />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label>Business Unit</Label>
                        <Input v-model="createBu" type="text" placeholder="Ex: Digital, Data, Cloud…" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1.5">
                            <Label>Début de période</Label>
                            <Input v-model="createStart" type="date" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Fin de période</Label>
                            <Input v-model="createEnd" type="date" :min="createStart" />
                        </div>
                    </div>
                </div>

                <p v-if="createError" class="rounded-lg bg-destructive-muted px-3 py-2 text-sm text-destructive">{{ createError }}</p>

                <DialogFooter>
                    <Button variant="outline" @click="closeCreateModal">
                        <X class="h-4 w-4" />
                        Annuler
                    </Button>
                    <Button :disabled="createLoading || !createProjet.trim()" @click="submitCreateFiche">
                        <LoaderCircle v-if="createLoading" class="h-4 w-4 animate-spin" />
                        {{ createLoading ? 'Création…' : 'Créer la fiche' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
