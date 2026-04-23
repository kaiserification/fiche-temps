<script setup>
import { useAppearance } from '@/composables/useAppearance';
import { Link, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import { LogOut, Monitor, Moon, Sun } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import CalendarGrid from '../components/CalendarGrid.vue';
import DayPanel from '../components/DayPanel.vue';

dayjs.extend(isSameOrBefore);
dayjs.extend(isSameOrAfter);
dayjs.locale('fr');

const props = defineProps({
    periodStart: String,
    periodEnd: String,
    authorName: String,
    matricule: String,
    profil: String,
    fiche: Object,
});

const { appearance, updateAppearance } = useAppearance();
const themeIcons = { light: Sun, dark: Moon, system: Monitor };
function cycleTheme() {
    const order = ['light', 'dark', 'system'];
    updateAppearance(order[(order.indexOf(appearance.value) + 1) % order.length]);
}

// Form fields
const selectedDay = ref(null);
const filledDays = ref({});
const dayEntries = ref({});
const projet = ref(props.fiche?.projet ?? '');
const bu = ref(props.fiche?.business_unit ?? '');
const localStart = ref(dayjs(props.fiche?.period_start ?? props.periodStart).format('YYYY-MM-DD'));
const localEnd = ref(dayjs(props.fiche?.period_end ?? props.periodEnd).format('YYYY-MM-DD'));

// UI state
const isSaving = ref(false);
const isEditing = ref(false);
const errorMsg = ref('');

function startEdit() {
    isEditing.value = true;
    errorMsg.value = '';
}

function cancelEdit() {
    projet.value = props.fiche?.projet ?? '';
    bu.value = props.fiche?.business_unit ?? '';
    localStart.value = dayjs(props.fiche?.period_start ?? props.periodStart).format('YYYY-MM-DD');
    localEnd.value = dayjs(props.fiche?.period_end ?? props.periodEnd).format('YYYY-MM-DD');
    isEditing.value = false;
    errorMsg.value = '';
}

async function updateFiche() {
    errorMsg.value = '';
    if (!projet.value.trim()) {
        errorMsg.value = 'Le nom du projet est requis.';
        return;
    }
    if (dayjs(localEnd.value).isBefore(dayjs(localStart.value))) {
        errorMsg.value = 'La date de fin doit être après la date de début.';
        return;
    }
    isSaving.value = true;
    try {
        const res = await fetch(`/fiche/${props.fiche.id}`, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-XSRF-TOKEN': getCsrf() },
            body: JSON.stringify({
                projet: projet.value,
                business_unit: bu.value,
                period_start: localStart.value,
                period_end: localEnd.value,
            }),
        });
        if (res.status === 419) {
            errorMsg.value = 'Session expirée. Veuillez recharger la page.';
        } else {
            const data = await res.json();
            if (!res.ok) {
                errorMsg.value = data.message ?? `Erreur HTTP ${res.status}`;
            } else {
                isEditing.value = false;
            }
        }
    } catch {
        errorMsg.value = 'Erreur réseau. Vérifiez votre connexion.';
    } finally {
        isSaving.value = false;
    }
}

// Pre-fill from existing fiche
if (props.fiche?.day_entries) {
    props.fiche.day_entries.forEach((e) => {
        filledDays.value[e.day] = e.tasks;
        dayEntries.value[e.day] = e;
    });
}

// Workdays computed client-side
const computedWorkdays = computed(() => {
    if (!localStart.value || !localEnd.value) return [];
    const start = dayjs(localStart.value);
    const end = dayjs(localEnd.value);
    if (!start.isValid() || !end.isValid() || end.isBefore(start)) return [];
    const days = [];
    let cur = start;
    while (cur.isSameOrBefore(end)) {
        if (cur.day() !== 0 && cur.day() !== 6) days.push(cur.format('YYYY-MM-DD'));
        cur = cur.add(1, 'day');
    }
    return days;
});

const periodLabel = computed(() => {
    if (!localStart.value || !localEnd.value) return '';
    return dayjs(localStart.value).format('D MMM') + ' → ' + dayjs(localEnd.value).format('D MMM YYYY');
});

const completionCount = computed(() => Object.keys(filledDays.value).length);
const totalWorkdays = computed(() => computedWorkdays.value.length);
const completionPct = computed(() => (totalWorkdays.value ? Math.round((completionCount.value / totalWorkdays.value) * 100) : 0));

function getCsrf() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

// ── Save a day (create or update) ────────────────────────────────────────────
async function saveDay(day, entryId, tasks) {
    errorMsg.value = '';
    isSaving.value = true;

    const url = entryId ? `/fiche/${props.fiche.id}/day/${entryId}` : `/fiche/${props.fiche.id}/day`;
    const method = entryId ? 'PUT' : 'POST';
    const body = entryId ? { tasks } : { day, tasks };

    try {
        const res = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-XSRF-TOKEN': getCsrf() },
            body: JSON.stringify(body),
        });
        if (res.status === 419) {
            errorMsg.value = 'Session expirée. Veuillez recharger la page.';
        } else {
            const data = await res.json();
            if (!res.ok) {
                errorMsg.value = data.message ?? `Erreur HTTP ${res.status}`;
            } else {
                filledDays.value[day] = data.tasks ?? tasks;
                dayEntries.value[day] = data.entry;
            }
        }
    } catch {
        errorMsg.value = 'Erreur réseau. Vérifiez votre connexion.';
    } finally {
        isSaving.value = false;
    }
}

// ── Fiche creation ────────────────────────────────────────────────────────────
function createFiche() {
    errorMsg.value = '';
    if (!projet.value.trim()) {
        errorMsg.value = 'Le nom du projet est requis.';
        return;
    }
    if (!localStart.value || !localEnd.value) {
        errorMsg.value = 'Les dates de début et de fin sont requises.';
        return;
    }
    if (dayjs(localEnd.value).isBefore(dayjs(localStart.value))) {
        errorMsg.value = 'La date de fin doit être après la date de début.';
        return;
    }
    router.post('/fiche', {
        projet: projet.value,
        business_unit: bu.value,
        period_start: localStart.value,
        period_end: localEnd.value,
    });
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 transition-colors dark:bg-gray-900">
        <!-- Top bar -->
        <div class="sticky top-0 z-10 border-b border-gray-200 bg-white px-6 py-3 dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <Link
                        href="/"
                        prefetch
                        class="shrink-0 rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                        title="Retour à la liste"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div class="min-w-0">
                        <h1 class="truncate text-sm font-semibold leading-tight text-gray-900 dark:text-gray-100">
                            {{ fiche ? fiche.projet : 'Nouvelle fiche' }}
                        </h1>
                        <p class="truncate text-[11px] text-gray-400 dark:text-gray-500">
                            {{ authorName }} · {{ matricule }} · {{ profil }} · {{ periodLabel }}
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    <div class="hidden items-center gap-2 sm:flex">
                        <div class="h-1.5 w-28 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                            <div class="h-full rounded-full bg-indigo-500 transition-all duration-300" :style="{ width: completionPct + '%' }" />
                        </div>
                        <span class="text-xs tabular-nums text-gray-500 dark:text-gray-400"> {{ completionCount }}/{{ totalWorkdays }} </span>
                    </div>
                    <button
                        @click="cycleTheme"
                        class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                    >
                        <component :is="themeIcons[appearance]" class="h-4 w-4" />
                    </button>
                    <a
                        v-if="fiche"
                        :href="`/fiche/${fiche.id}/export`"
                        class="flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-emerald-700"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            />
                        </svg>
                        Exporter .xlsx
                    </a>
                    <button
                        @click="router.post('/logout')"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                        title="Déconnexion"
                    >
                        <LogOut class="h-3.5 w-3.5" />
                        <span>Déconnexion</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-6 py-6">
            <!-- Error banner -->
            <Transition name="slide-down">
                <div
                    v-if="errorMsg"
                    class="mb-5 flex items-center justify-between rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400"
                >
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                        <span>{{ errorMsg }}</span>
                    </div>
                    <button @click="errorMsg = ''" class="ml-4 text-lg leading-none text-red-400 hover:text-red-600">×</button>
                </div>
            </Transition>

            <!-- Form card -->
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                <!-- Header row when fiche exists -->
                <div v-if="fiche" class="mb-4 flex items-center justify-between">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Informations de la fiche</p>
                    <button
                        v-if="!isEditing"
                        @click="startEdit"
                        class="flex items-center gap-1.5 text-xs text-indigo-600 transition-colors hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.172-8.172z"
                            />
                        </svg>
                        Modifier
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">Projet</label>
                        <input
                            v-model="projet"
                            :readonly="fiche && !isEditing"
                            placeholder="Nom du projet"
                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 read-only:cursor-default read-only:bg-gray-50 read-only:text-gray-600 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500 dark:read-only:bg-gray-800 dark:read-only:text-gray-400"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">Business Unit</label>
                        <input
                            v-model="bu"
                            :readonly="fiche && !isEditing"
                            placeholder="Ex: Digital, Data, Cloud…"
                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 read-only:cursor-default read-only:bg-gray-50 read-only:text-gray-600 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-500 dark:read-only:bg-gray-800 dark:read-only:text-gray-400"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">Début de période</label>
                        <input
                            v-model="localStart"
                            :readonly="fiche && !isEditing"
                            type="date"
                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 read-only:cursor-default read-only:bg-gray-50 read-only:text-gray-600 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:read-only:bg-gray-800 dark:read-only:text-gray-400"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">Fin de période</label>
                        <input
                            v-model="localEnd"
                            :readonly="fiche && !isEditing"
                            type="date"
                            :min="localStart"
                            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 read-only:cursor-default read-only:bg-gray-50 read-only:text-gray-600 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:read-only:bg-gray-800 dark:read-only:text-gray-400"
                        />
                    </div>
                </div>

                <p v-if="!fiche && computedWorkdays.length" class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                    {{ computedWorkdays.length }} jours ouvrables sur cette période
                </p>

                <!-- Actions : création -->
                <div v-if="!fiche" class="mt-4 flex items-center justify-end gap-3">
                    <p v-if="!projet.trim()" class="text-xs text-gray-400 dark:text-gray-500">Renseignez le projet pour continuer</p>
                    <button
                        @click="createFiche"
                        :disabled="!projet.trim() || !localStart || !localEnd"
                        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Créer la fiche
                    </button>
                </div>

                <!-- Actions : édition -->
                <div v-if="fiche && isEditing" class="mt-4 flex items-center justify-end gap-2">
                    <button
                        @click="cancelEdit"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700"
                    >
                        Annuler
                    </button>
                    <button
                        @click="updateFiche"
                        :disabled="isSaving"
                        class="flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <svg v-if="isSaving" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ isSaving ? 'Sauvegarde…' : 'Enregistrer les modifications' }}
                    </button>
                </div>
            </div>

            <!-- Calendar + Panel -->
            <div class="grid grid-cols-3 gap-6">
                <div class="col-span-2">
                    <CalendarGrid
                        :workdays="computedWorkdays"
                        :filled-days="filledDays"
                        :selected-day="selectedDay"
                        :period-start="localStart"
                        :period-end="localEnd"
                        @select="selectedDay = $event"
                    />
                </div>

                <div>
                    <DayPanel
                        v-if="selectedDay"
                        :day="selectedDay"
                        :entry-id="dayEntries[selectedDay]?.id ?? null"
                        :tasks="filledDays[selectedDay] || []"
                        :saving="isSaving"
                        :fiche-exists="!!fiche"
                        @save="saveDay"
                    />
                    <div v-else class="rounded-xl border border-gray-200 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-800">
                        <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-gray-50 dark:bg-gray-700">
                            <svg class="h-5 w-5 text-gray-300 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <p class="text-sm leading-relaxed text-gray-400 dark:text-gray-500">Sélectionnez un jour<br />ouvrable dans le calendrier</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.2s ease;
}
.slide-down-enter-from,
.slide-down-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
