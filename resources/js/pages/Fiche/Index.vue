<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Sun, Moon, Monitor } from 'lucide-vue-next'
import { useAppearance } from '@/composables/useAppearance'
import dayjs from 'dayjs'
import 'dayjs/locale/fr'

dayjs.locale('fr')

const props = defineProps({
  fiches: Array,
})

const { appearance, updateAppearance } = useAppearance()

const confirmingDelete = ref(null) // id de la fiche en cours de confirmation

function deleteFiche(id) {
  router.delete(`/fiche/${id}`, {
    onSuccess: () => { confirmingDelete.value = null },
  })
}

const themeIcons = { light: Sun, dark: Moon, system: Monitor }
function cycleTheme() {
  const order = ['light', 'dark', 'system']
  const next = order[(order.indexOf(appearance.value) + 1) % order.length]
  updateAppearance(next)
}

function formatPeriod(start, end) {
  return dayjs(start).format('D MMM') + ' → ' + dayjs(end).format('D MMM YYYY')
}

function progressPct(fiche) {
  return Math.min(100, Math.round((fiche.day_entries_count / 22) * 100))
}

function statusLabel(count) {
  if (count === 0)  return 'Non commencée'
  if (count >= 20)  return 'Complète'
  return 'En cours'
}

function statusClass(count) {
  if (count === 0)  return 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
  if (count >= 20)  return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
  return 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400'
}

function progressBarClass(count) {
  if (count === 0)  return 'bg-gray-200 dark:bg-gray-600'
  if (count >= 20)  return 'bg-emerald-500'
  return 'bg-indigo-500'
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors">

    <!-- Top bar -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
      <div class="max-w-5xl mx-auto flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Fiches de temps</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
            {{ fiches.length }} période{{ fiches.length !== 1 ? 's' : '' }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <!-- Theme toggle -->
          <button
            @click="cycleTheme"
            class="p-2 rounded-lg text-gray-400 dark:text-gray-500
                   hover:text-gray-600 dark:hover:text-gray-300
                   hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            :title="appearance"
          >
            <component :is="themeIcons[appearance]" class="w-4 h-4" />
          </button>
          <!-- New fiche -->
          <button
            @click="router.visit('/fiche/creer')"
            class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm
                   font-medium rounded-lg hover:bg-indigo-700 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nouvelle fiche
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-5xl mx-auto px-6 py-8">

      <!-- Empty state -->
      <div v-if="!fiches.length" class="text-center py-24">
        <div class="w-14 h-14 bg-gray-100 dark:bg-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <svg class="w-7 h-7 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">Aucune fiche</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Commencez par créer votre première fiche de temps.</p>
        <button
          @click="router.visit('/fiche/creer')"
          class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors"
        >
          Créer une fiche
        </button>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="fiche in fiches"
          :key="fiche.id"
          @click="router.visit(`/fiche/${fiche.id}`)"
          class="group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                 rounded-xl p-5 cursor-pointer
                 hover:border-indigo-300 dark:hover:border-indigo-600
                 hover:shadow-md dark:hover:shadow-indigo-900/20
                 transition-all duration-150"
        >
          <!-- Header -->
          <div class="flex items-start justify-between mb-3">
            <div class="flex-1 min-w-0">
              <p class="text-[11px] text-gray-400 dark:text-gray-500 mb-1 font-medium tracking-wide">
                {{ formatPeriod(fiche.period_start, fiche.period_end) }}
              </p>
              <h2 class="font-semibold text-gray-900 dark:text-gray-100 text-sm truncate
                         group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                {{ fiche.projet || 'Projet non renseigné' }}
              </h2>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                {{ fiche.business_unit || '—' }}
              </p>
            </div>
            <span class="ml-3 shrink-0 text-[10px] font-semibold px-2 py-0.5 rounded-full"
              :class="statusClass(fiche.day_entries_count)">
              {{ statusLabel(fiche.day_entries_count) }}
            </span>
          </div>

          <!-- Progress -->
          <div class="mb-4">
            <div class="flex justify-between items-center mb-1.5">
              <span class="text-[10px] text-gray-400 dark:text-gray-500">Progression</span>
              <span class="text-[10px] font-medium text-gray-600 dark:text-gray-400 tabular-nums">
                {{ fiche.day_entries_count }} / 22 jours
              </span>
            </div>
            <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-500"
                :class="progressBarClass(fiche.day_entries_count)"
                :style="{ width: progressPct(fiche) + '%' }"
              />
            </div>
          </div>

          <!-- Actions -->
          <div v-if="confirmingDelete !== fiche.id" class="flex gap-2">
            <button
              @click.stop="router.visit(`/fiche/${fiche.id}`)"
              class="flex-1 text-xs font-medium border border-gray-200 dark:border-gray-600
                     rounded-lg py-1.5 text-gray-600 dark:text-gray-400
                     hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              Ouvrir
            </button>
            <a
              :href="`/fiche/${fiche.id}/export`"
              @click.stop
              class="flex-1 text-xs font-medium border border-emerald-200 dark:border-emerald-800
                     rounded-lg py-1.5 text-center text-emerald-700 dark:text-emerald-400
                     hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-colors"
            >
              Exporter .xlsx
            </a>
            <button
              @click.stop="confirmingDelete = fiche.id"
              class="p-1.5 text-gray-300 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400
                     hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
              title="Supprimer"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>

          <!-- Confirmation suppression -->
          <div v-else @click.stop class="flex items-center gap-2">
            <p class="flex-1 text-xs text-red-600 dark:text-red-400 font-medium">
              Supprimer cette fiche ?
            </p>
            <button
              @click.stop="deleteFiche(fiche.id)"
              class="text-xs font-medium px-2.5 py-1.5 bg-red-600 text-white rounded-lg
                     hover:bg-red-700 transition-colors"
            >
              Confirmer
            </button>
            <button
              @click.stop="confirmingDelete = null"
              class="text-xs font-medium px-2.5 py-1.5 border border-gray-200 dark:border-gray-600
                     text-gray-600 dark:text-gray-400 rounded-lg
                     hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              Annuler
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
