<script setup>
import { useAppearance } from '@/composables/useAppearance';
import { Link, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import { GitBranch, LogOut, Monitor, Moon, Sun } from 'lucide-vue-next';
import { ref } from 'vue';

dayjs.locale('fr');

const props = defineProps({
    fiches: Array,
});

const { appearance, updateAppearance } = useAppearance();

const confirmingDelete = ref(null);

// ── Git → Fiche ──────────────────────────────────────────────────────────────
const gitModalOpen  = ref(false);
const gitDate       = ref(dayjs().format('YYYY-MM-DD'));
const gitLoading    = ref(false);
const gitError      = ref('');
const gitResult     = ref('');
const gitEmpty      = ref(false);
const copied        = ref(false);
const gitProjects   = ref([]);
const gitProject    = ref('');

async function loadProjects() {
    try {
        const res  = await fetch('/git-projects', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        gitProjects.value = data.projects ?? [];
        // default: current project name derived from URL (last segment of base path)
        const current = window.location.hostname.split('.')[0];
        gitProject.value  = gitProjects.value.includes(current) ? current : (gitProjects.value[0] ?? '');
    } catch {
        gitProjects.value = [];
    }
}

async function openGitModal() {
    gitDate.value    = dayjs().format('YYYY-MM-DD');
    gitResult.value  = '';
    gitError.value   = '';
    gitEmpty.value   = false;
    copied.value     = false;
    gitModalOpen.value = true;
    await loadProjects();
}

function closeGitModal() {
    gitModalOpen.value = false;
}

function getCsrf() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function generateFromGit() {
    gitError.value  = '';
    gitResult.value = '';
    gitEmpty.value  = false;
    gitLoading.value = true;
    try {
        const res = await fetch('/git-to-timesheet', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
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

function deleteFiche(id) {
    router.delete(`/fiche/${id}`, {
        onSuccess: () => {
            confirmingDelete.value = null;
        },
    });
}

const themeIcons = { light: Sun, dark: Moon, system: Monitor };
function cycleTheme() {
    const order = ['light', 'dark', 'system'];
    const next = order[(order.indexOf(appearance.value) + 1) % order.length];
    updateAppearance(next);
}

function formatPeriod(start, end) {
    return dayjs(start).format('D MMM') + ' → ' + dayjs(end).format('D MMM YYYY');
}

function progressPct(fiche) {
    return Math.min(100, Math.round((fiche.day_entries_count / 22) * 100));
}

function statusLabel(count) {
    if (count === 0) return 'Non commencée';
    if (count >= 20) return 'Complète';
    return 'En cours';
}

function statusClass(count) {
    if (count === 0) return 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400';
    if (count >= 20) return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400';
    return 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400';
}

function progressBarClass(count) {
    if (count === 0) return 'bg-gray-200 dark:bg-gray-600';
    if (count >= 20) return 'bg-emerald-500';
    return 'bg-indigo-500';
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 transition-colors dark:bg-gray-900">
        <!-- Top bar -->
        <div class="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto flex max-w-5xl items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Fiches de temps</h1>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ fiches.length }} période{{ fiches.length !== 1 ? 's' : '' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Theme toggle -->
                    <button
                        @click="cycleTheme"
                        class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                        :title="appearance"
                    >
                        <component :is="themeIcons[appearance]" class="h-4 w-4" />
                    </button>
                    <!-- Logout -->
                    <button
                        @click="router.post('/logout')"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                        title="Déconnexion"
                    >
                        <LogOut class="h-4 w-4" />
                        <span>Déconnexion</span>
                    </button>
                    <!-- Git → Fiche -->
                    <button
                        @click="openGitModal"
                        class="flex items-center gap-2 rounded-lg border border-indigo-200 px-4 py-2 text-sm font-medium text-indigo-600 transition-colors hover:bg-indigo-50 dark:border-indigo-700 dark:text-indigo-400 dark:hover:bg-indigo-900/20"
                    >
                        <GitBranch class="h-4 w-4" />
                        Git → Fiche
                    </button>
                    <!-- New fiche -->
                    <Link
                        href="/fiche/creer"
                        prefetch
                        class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nouvelle fiche
                    </Link>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-5xl px-6 py-8">
            <!-- Empty state -->
            <div v-if="!fiches.length" class="py-24 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800">
                    <svg class="h-7 w-7 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                </div>
                <h2 class="mb-1 text-sm font-semibold text-gray-900 dark:text-gray-100">Aucune fiche</h2>
                <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">Commencez par créer votre première fiche de temps.</p>
                <button
                    @click="router.visit('/fiche/creer')"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
                >
                    Créer une fiche
                </button>
            </div>

            <!-- Grid -->
            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="fiche in fiches"
                    :key="fiche.id"
                    @click="router.visit(`/fiche/${fiche.id}`)"
                    class="group cursor-pointer rounded-xl border border-gray-200 bg-white p-5 transition-all duration-150 hover:border-indigo-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-indigo-600 dark:hover:shadow-indigo-900/20"
                >
                    <!-- Header -->
                    <div class="mb-3 flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="mb-1 text-[11px] font-medium tracking-wide text-gray-400 dark:text-gray-500">
                                {{ formatPeriod(fiche.period_start, fiche.period_end) }}
                            </p>
                            <h2
                                class="truncate text-sm font-semibold text-gray-900 transition-colors group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                            >
                                {{ fiche.projet || 'Projet non renseigné' }}
                            </h2>
                            <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ fiche.business_unit || '—' }}
                            </p>
                        </div>
                        <span class="ml-3 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="statusClass(fiche.day_entries_count)">
                            {{ statusLabel(fiche.day_entries_count) }}
                        </span>
                    </div>

                    <!-- Progress -->
                    <div class="mb-4">
                        <div class="mb-1.5 flex items-center justify-between">
                            <span class="text-[10px] text-gray-400 dark:text-gray-500">Progression</span>
                            <span class="text-[10px] font-medium tabular-nums text-gray-600 dark:text-gray-400">
                                {{ fiche.day_entries_count }} / 22 jours
                            </span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="progressBarClass(fiche.day_entries_count)"
                                :style="{ width: progressPct(fiche) + '%' }"
                            />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div v-if="confirmingDelete !== fiche.id" class="flex gap-2">
                        <Link
                            :href="`/fiche/${fiche.id}`"
                            prefetch
                            class="flex-1 text-center rounded-lg border border-gray-200 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700"
                        >
                            Ouvrir
                        </Link>
                        <a
                            :href="`/fiche/${fiche.id}/export`"
                            @click.stop
                            class="flex-1 rounded-lg border border-emerald-200 py-1.5 text-center text-xs font-medium text-emerald-700 transition-colors hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-400 dark:hover:bg-emerald-900/30"
                        >
                            Exporter .xlsx
                        </a>
                        <button
                            @click.stop="confirmingDelete = fiche.id"
                            class="rounded-lg p-1.5 text-gray-300 transition-colors hover:bg-red-50 hover:text-red-500 dark:text-gray-600 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                            title="Supprimer"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Confirmation suppression -->
                    <div v-else @click.stop class="flex items-center gap-2">
                        <p class="flex-1 text-xs font-medium text-red-600 dark:text-red-400">Supprimer cette fiche ?</p>
                        <button
                            @click.stop="deleteFiche(fiche.id)"
                            class="rounded-lg bg-red-600 px-2.5 py-1.5 text-xs font-medium text-white transition-colors hover:bg-red-700"
                        >
                            Confirmer
                        </button>
                        <button
                            @click.stop="confirmingDelete = null"
                            class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700"
                        >
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Git → Fiche modal ──────────────────────────────────────────────── -->
    <Teleport to="body">
        <div
            v-if="gitModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
            @click.self="closeGitModal"
        >
            <div class="flex w-full max-w-lg flex-col gap-5 rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-800">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/40">
                            <GitBranch class="h-4 w-4 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Git → Fiche de temps</h2>
                    </div>
                    <button
                        @click="closeGitModal"
                        class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Project selector -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Projet</label>
                    <select
                        v-model="gitProject"
                        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-900/30"
                    >
                        <option v-if="!gitProjects.length" value="" disabled>Chargement…</option>
                        <option v-for="p in gitProjects" :key="p" :value="p">{{ p }}</option>
                    </select>
                </div>

                <!-- Date picker -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Date</label>
                    <input
                        v-model="gitDate"
                        type="date"
                        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:focus:border-indigo-500 dark:focus:ring-indigo-900/30"
                    />
                </div>

                <!-- Generate button -->
                <button
                    @click="generateFromGit"
                    :disabled="gitLoading"
                    class="flex items-center justify-center gap-2 rounded-lg bg-indigo-600 py-2.5 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <svg v-if="gitLoading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    <GitBranch v-else class="h-4 w-4" />
                    {{ gitLoading ? 'Génération en cours…' : 'Générer les tâches' }}
                </button>

                <!-- Error -->
                <p v-if="gitError" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600 dark:bg-red-900/20 dark:text-red-400">
                    {{ gitError }}
                </p>

                <!-- Empty state -->
                <p v-if="gitEmpty" class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                    Aucun commit trouvé pour le {{ dayjs(gitDate).locale('fr').format('D MMMM YYYY') }}.
                </p>

                <!-- Result -->
                <div v-if="gitResult" class="flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Tâches générées</span>
                        <button
                            @click="copyResult"
                            class="flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium transition-colors"
                            :class="copied
                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'"
                        >
                            <svg v-if="copied" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            {{ copied ? 'Copié !' : 'Copier' }}
                        </button>
                    </div>
                    <pre class="max-h-64 overflow-y-auto whitespace-pre-wrap rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm leading-relaxed text-gray-800 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-200">{{ gitResult }}</pre>
                </div>
            </div>
        </div>
    </Teleport>
</template>
