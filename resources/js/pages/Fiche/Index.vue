<script setup>
import { useAppearance } from '@/composables/useAppearance';
import { Link, router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import 'dayjs/locale/fr';
import { LogOut, Monitor, Moon, Sun } from 'lucide-vue-next';
import { ref } from 'vue';

dayjs.locale('fr');

const props = defineProps({
    fiches: Array,
});

const { appearance, updateAppearance } = useAppearance();

const confirmingDelete = ref(null); // id de la fiche en cours de confirmation

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
</template>
