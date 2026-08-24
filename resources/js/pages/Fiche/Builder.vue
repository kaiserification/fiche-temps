<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import { AlertTriangle, CalendarDays, LoaderCircle, Pencil } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import CalendarGrid from '../components/CalendarGrid.vue';
import DayPanel from '../components/DayPanel.vue';

dayjs.extend(isSameOrBefore);
dayjs.extend(isSameOrAfter);
dayjs.locale('fr');

const props = defineProps<{
    periodStart?: string;
    periodEnd?: string;
    authorName?: string;
    matricule?: string;
    profil?: string;
    fiche?: {
        id: number;
        projet: string | null;
        business_unit: string | null;
        period_start: string;
        period_end: string;
        day_entries?: Array<{ day: string; tasks: unknown; comment?: string; projet?: string | null }>;
    };
}>();

// Form fields
const selectedDay = ref<string | null>(null);
const filledDays = ref<Record<string, unknown>>({});
const dayEntries = ref<Record<string, any>>({});
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
        const res = await fetch(`/fiche/${props.fiche!.id}`, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': getCsrf() },
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

const allowWeekends = ref(false);

// Workdays computed client-side
const computedWorkdays = computed(() => {
    if (!localStart.value || !localEnd.value) return [];
    const start = dayjs(localStart.value);
    const end = dayjs(localEnd.value);
    if (!start.isValid() || !end.isValid() || end.isBefore(start)) return [];
    const days: string[] = [];
    let cur = start;
    while (cur.isSameOrBefore(end)) {
        if (allowWeekends.value || (cur.day() !== 0 && cur.day() !== 6)) days.push(cur.format('YYYY-MM-DD'));
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
async function saveDay(day: string, entryId: number | null, tasks: unknown, comment: string, dayProjet: string) {
    errorMsg.value = '';
    isSaving.value = true;

    const url = entryId ? `/fiche/${props.fiche!.id}/day/${entryId}` : `/fiche/${props.fiche!.id}/day`;
    const method = entryId ? 'PUT' : 'POST';
    const body = entryId ? { tasks, comment, projet: dayProjet } : { day, tasks, comment, projet: dayProjet };

    try {
        const res = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': getCsrf() },
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
    <Head :title="fiche ? fiche.projet ?? 'Fiche de temps' : 'Nouvelle fiche'" />

    <AppLayout>
        <template #header>
            <PageHeader :title="fiche ? fiche.projet || 'Fiche de temps' : 'Nouvelle fiche'" :description="`${authorName} · ${matricule} · ${profil} · ${periodLabel}`">
                <template #actions>
                    <div v-if="fiche" class="mr-2 hidden items-center gap-2 sm:flex">
                        <Progress :model-value="completionPct" class="h-1.5 w-28" />
                        <span class="text-xs tabular-nums text-muted-foreground">{{ completionCount }}/{{ totalWorkdays }}</span>
                    </div>
                    <template v-if="fiche">
                        <Button as-child variant="outline" size="sm">
                            <a :href="`/fiche/${fiche.id}/export`">.xlsx</a>
                        </Button>
                        <Button as-child variant="outline" size="sm">
                            <a :href="`/fiche/${fiche.id}/export/pdf`">.pdf</a>
                        </Button>
                    </template>
                </template>
            </PageHeader>
        </template>

        <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6">
            <!-- Error banner -->
            <Transition name="slide-down">
                <div
                    v-if="errorMsg"
                    class="mb-5 flex items-center justify-between rounded-xl border border-destructive/30 bg-destructive-muted px-4 py-3 text-sm text-destructive"
                >
                    <div class="flex items-center gap-2">
                        <AlertTriangle class="h-4 w-4 shrink-0" />
                        <span>{{ errorMsg }}</span>
                    </div>
                    <button type="button" @click="errorMsg = ''" class="ml-4 text-lg leading-none text-destructive/70 hover:text-destructive">×</button>
                </div>
            </Transition>

            <!-- Form card -->
            <div class="mb-6 rounded-xl border border-border bg-card p-5 shadow-soft-sm">
                <!-- Header row when fiche exists -->
                <div v-if="fiche" class="mb-4 flex items-center justify-between">
                    <p class="text-xs font-medium text-muted-foreground">Informations de la fiche</p>
                    <button
                        v-if="!isEditing"
                        type="button"
                        @click="startEdit"
                        class="flex items-center gap-1.5 text-xs text-primary transition-colors hover:text-primary/80"
                    >
                        <Pencil class="h-3.5 w-3.5" />
                        Modifier
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label class="mb-1.5 block">Projet</Label>
                        <Input v-model="projet" :readonly="!!fiche && !isEditing" placeholder="Nom du projet" />
                    </div>

                    <div>
                        <Label class="mb-1.5 block">Business Unit</Label>
                        <Input v-model="bu" :readonly="!!fiche && !isEditing" placeholder="Ex: Digital, Data, Cloud…" />
                    </div>

                    <div>
                        <Label class="mb-1.5 block">Début de période</Label>
                        <Input v-model="localStart" :readonly="!!fiche && !isEditing" type="date" />
                    </div>

                    <div>
                        <Label class="mb-1.5 block">Fin de période</Label>
                        <Input v-model="localEnd" :readonly="!!fiche && !isEditing" type="date" :min="localStart" />
                    </div>
                </div>

                <p v-if="!fiche && computedWorkdays.length" class="mt-2 text-xs text-muted-foreground">
                    {{ computedWorkdays.length }} jours ouvrables sur cette période
                </p>

                <!-- Actions : création -->
                <div v-if="!fiche" class="mt-4 flex items-center justify-end gap-3">
                    <p v-if="!projet.trim()" class="text-xs text-muted-foreground">Renseignez le projet pour continuer</p>
                    <Button :disabled="!projet.trim() || !localStart || !localEnd" @click="createFiche">Créer la fiche</Button>
                </div>

                <!-- Actions : édition -->
                <div v-if="fiche && isEditing" class="mt-4 flex items-center justify-end gap-2">
                    <Button variant="outline" @click="cancelEdit">Annuler</Button>
                    <Button :disabled="isSaving" @click="updateFiche">
                        <LoaderCircle v-if="isSaving" class="h-3.5 w-3.5 animate-spin" />
                        {{ isSaving ? 'Sauvegarde…' : 'Enregistrer les modifications' }}
                    </Button>
                </div>
            </div>

            <!-- Calendar + Panel -->
            <div class="grid grid-cols-3 gap-6" v-if="props.fiche">
                <div class="col-span-2">
                    <div class="mb-3 flex items-center justify-end">
                        <label class="flex cursor-pointer select-none items-center gap-2 text-xs text-muted-foreground">
                            <span>Inclure les week-ends</span>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="allowWeekends"
                                @click="allowWeekends = !allowWeekends"
                                :class="[
                                    'relative inline-flex h-5 w-9 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-warning focus:ring-offset-1',
                                    allowWeekends ? 'bg-warning' : 'bg-muted',
                                ]"
                            >
                                <span
                                    :class="[
                                        'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200',
                                        allowWeekends ? 'translate-x-4' : 'translate-x-0',
                                    ]"
                                />
                            </button>
                        </label>
                    </div>
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
                        :comment="dayEntries[selectedDay]?.comment ?? ''"
                        :projet="dayEntries[selectedDay]?.projet ?? ''"
                        :fiche-projet="projet"
                        :saving="isSaving"
                        :fiche-exists="!!fiche"
                        @save="saveDay"
                    />
                    <div v-else class="rounded-xl border border-border bg-card p-10 text-center shadow-soft-sm">
                        <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-muted">
                            <CalendarDays class="h-5 w-5 text-muted-foreground" />
                        </div>
                        <p class="text-sm leading-relaxed text-muted-foreground">Sélectionnez un jour<br />ouvrable dans le calendrier</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
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
