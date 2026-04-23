<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Sun, Moon, Monitor } from 'lucide-vue-next'
import { useAppearance } from '@/composables/useAppearance'
import dayjs from 'dayjs'
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore'
import isSameOrAfter  from 'dayjs/plugin/isSameOrAfter'
import 'dayjs/locale/fr'
import CalendarGrid from '../components/CalendarGrid.vue'
import DayPanel from '../components/DayPanel.vue'

dayjs.extend(isSameOrBefore)
dayjs.extend(isSameOrAfter)
dayjs.locale('fr')

const props = defineProps({
  periodStart: String,
  periodEnd:   String,
  authorName:  String,
  matricule:   String,
  profil:      String,
  fiche:       Object,
})

const { appearance, updateAppearance } = useAppearance()
const themeIcons = { light: Sun, dark: Moon, system: Monitor }
function cycleTheme() {
  const order = ['light', 'dark', 'system']
  updateAppearance(order[(order.indexOf(appearance.value) + 1) % order.length])
}

// Form fields
const selectedDay = ref(null)
const filledDays  = ref({})
const dayEntries  = ref({})
const projet      = ref(props.fiche?.projet ?? '')
const bu          = ref(props.fiche?.business_unit ?? '')
const localStart  = ref(dayjs(props.fiche?.period_start ?? props.periodStart).format('YYYY-MM-DD'))
const localEnd    = ref(dayjs(props.fiche?.period_end   ?? props.periodEnd).format('YYYY-MM-DD'))

// UI state
const isSaving   = ref(false)
const isEditing  = ref(false)
const errorMsg   = ref('')

function startEdit() {
  isEditing.value = true
  errorMsg.value  = ''
}

function cancelEdit() {
  projet.value     = props.fiche?.projet ?? ''
  bu.value         = props.fiche?.business_unit ?? ''
  localStart.value = dayjs(props.fiche?.period_start ?? props.periodStart).format('YYYY-MM-DD')
  localEnd.value   = dayjs(props.fiche?.period_end   ?? props.periodEnd).format('YYYY-MM-DD')
  isEditing.value  = false
  errorMsg.value   = ''
}

async function updateFiche() {
  errorMsg.value = ''
  if (!projet.value.trim()) { errorMsg.value = 'Le nom du projet est requis.'; return }
  if (dayjs(localEnd.value).isBefore(dayjs(localStart.value))) {
    errorMsg.value = 'La date de fin doit être après la date de début.'
    return
  }
  isSaving.value = true
  try {
    const res  = await fetch(`/fiche/${props.fiche.id}`, {
      method:      'PATCH',
      credentials: 'same-origin',
      headers:     { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
      body:        JSON.stringify({
        projet:        projet.value,
        business_unit: bu.value,
        period_start:  localStart.value,
        period_end:    localEnd.value,
      }),
    })
    const data = await res.json()
    if (!res.ok) {
      errorMsg.value = data.message ?? `Erreur HTTP ${res.status}`
    } else {
      isEditing.value = false
    }
  } catch {
    errorMsg.value = 'Erreur réseau. Vérifiez votre connexion.'
  } finally {
    isSaving.value = false
  }
}

// Pre-fill from existing fiche
if (props.fiche?.day_entries) {
  props.fiche.day_entries.forEach(e => {
    filledDays.value[e.day] = e.tasks
    dayEntries.value[e.day] = e
  })
}

// Workdays computed client-side
const computedWorkdays = computed(() => {
  if (!localStart.value || !localEnd.value) return []
  const start = dayjs(localStart.value)
  const end   = dayjs(localEnd.value)
  if (!start.isValid() || !end.isValid() || end.isBefore(start)) return []
  const days = []
  let cur = start
  while (cur.isSameOrBefore(end)) {
    if (cur.day() !== 0 && cur.day() !== 6) days.push(cur.format('YYYY-MM-DD'))
    cur = cur.add(1, 'day')
  }
  return days
})

const periodLabel = computed(() => {
  if (!localStart.value || !localEnd.value) return ''
  return dayjs(localStart.value).format('D MMM') + ' → ' + dayjs(localEnd.value).format('D MMM YYYY')
})

const completionCount = computed(() => Object.keys(filledDays.value).length)
const totalWorkdays   = computed(() => computedWorkdays.value.length)
const completionPct   = computed(() =>
  totalWorkdays.value ? Math.round(completionCount.value / totalWorkdays.value * 100) : 0
)

function getCsrf() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? ''
}

// ── Save a day (create or update) ────────────────────────────────────────────
async function saveDay(day, entryId, tasks) {
  errorMsg.value = ''
  isSaving.value = true

  const url    = entryId ? `/fiche/${props.fiche.id}/day/${entryId}` : `/fiche/${props.fiche.id}/day`
  const method = entryId ? 'PUT' : 'POST'
  const body   = entryId ? { tasks } : { day, tasks }

  try {
    const res  = await fetch(url, {
      method,
      credentials: 'same-origin',
      headers:     { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
      body:        JSON.stringify(body),
    })
    const data = await res.json()
    if (!res.ok) {
      errorMsg.value = data.message ?? `Erreur HTTP ${res.status}`
    } else {
      filledDays.value[day] = data.tasks ?? tasks
      dayEntries.value[day] = data.entry
    }
  } catch {
    errorMsg.value = 'Erreur réseau. Vérifiez votre connexion.'
  } finally {
    isSaving.value = false
  }
}

// ── Fiche creation ────────────────────────────────────────────────────────────
function createFiche() {
  errorMsg.value = ''
  if (!projet.value.trim()) {
    errorMsg.value = 'Le nom du projet est requis.'
    return
  }
  if (!localStart.value || !localEnd.value) {
    errorMsg.value = 'Les dates de début et de fin sont requises.'
    return
  }
  if (dayjs(localEnd.value).isBefore(dayjs(localStart.value))) {
    errorMsg.value = 'La date de fin doit être après la date de début.'
    return
  }
  router.post('/fiche', {
    projet:        projet.value,
    business_unit: bu.value,
    period_start:  localStart.value,
    period_end:    localEnd.value,
  })
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors">

    <!-- Top bar -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-3 sticky top-0 z-10">
      <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">

        <div class="flex items-center gap-3 min-w-0">
          <button
            @click="router.visit('/')"
            class="shrink-0 p-1.5 text-gray-400 dark:text-gray-500
                   hover:text-gray-600 dark:hover:text-gray-300
                   hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            title="Retour à la liste"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div class="min-w-0">
            <h1 class="text-sm font-semibold text-gray-900 dark:text-gray-100 leading-tight truncate">
              {{ fiche ? fiche.projet : 'Nouvelle fiche' }}
            </h1>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 truncate">
              {{ authorName }} · {{ matricule }} · {{ profil }} · {{ periodLabel }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-3 shrink-0">
          <div class="hidden sm:flex items-center gap-2">
            <div class="w-28 h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
              <div
                class="h-full bg-indigo-500 rounded-full transition-all duration-300"
                :style="{ width: completionPct + '%' }"
              />
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-400 tabular-nums">
              {{ completionCount }}/{{ totalWorkdays }}
            </span>
          </div>
          <button
            @click="cycleTheme"
            class="p-1.5 rounded-lg text-gray-400 dark:text-gray-500
                   hover:text-gray-600 dark:hover:text-gray-300
                   hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <component :is="themeIcons[appearance]" class="w-4 h-4" />
          </button>
          <a
            v-if="fiche"
            :href="`/fiche/${fiche.id}/export`"
            class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white
                   text-xs font-medium rounded-lg hover:bg-emerald-700 transition-colors"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Exporter .xlsx
          </a>
        </div>

      </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-6">

      <!-- Error banner -->
      <Transition name="slide-down">
        <div
          v-if="errorMsg"
          class="mb-5 flex items-center justify-between
                 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800
                 text-red-700 dark:text-red-400 text-sm rounded-xl px-4 py-3"
        >
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ errorMsg }}</span>
          </div>
          <button @click="errorMsg = ''" class="text-red-400 hover:text-red-600 ml-4 text-lg leading-none">×</button>
        </div>
      </Transition>

      <!-- Form card -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 mb-6">

        <!-- Header row when fiche exists -->
        <div v-if="fiche" class="flex items-center justify-between mb-4">
          <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Informations de la fiche</p>
          <button
            v-if="!isEditing"
            @click="startEdit"
            class="flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400
                   hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.172-8.172z" />
            </svg>
            Modifier
          </button>
        </div>

        <div class="grid grid-cols-2 gap-4">

          <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Projet</label>
            <input v-model="projet" :readonly="fiche && !isEditing" placeholder="Nom du projet"
              class="w-full border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 text-sm
                     bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                     placeholder-gray-400 dark:placeholder-gray-500
                     focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                     read-only:bg-gray-50 dark:read-only:bg-gray-800
                     read-only:text-gray-600 dark:read-only:text-gray-400 read-only:cursor-default" />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Business Unit</label>
            <input v-model="bu" :readonly="fiche && !isEditing" placeholder="Ex: Digital, Data, Cloud…"
              class="w-full border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 text-sm
                     bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                     placeholder-gray-400 dark:placeholder-gray-500
                     focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                     read-only:bg-gray-50 dark:read-only:bg-gray-800
                     read-only:text-gray-600 dark:read-only:text-gray-400 read-only:cursor-default" />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Début de période</label>
            <input v-model="localStart" :readonly="fiche && !isEditing" type="date"
              class="w-full border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 text-sm
                     bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                     focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                     read-only:bg-gray-50 dark:read-only:bg-gray-800
                     read-only:text-gray-600 dark:read-only:text-gray-400 read-only:cursor-default" />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Fin de période</label>
            <input v-model="localEnd" :readonly="fiche && !isEditing" type="date" :min="localStart"
              class="w-full border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 text-sm
                     bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                     focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                     read-only:bg-gray-50 dark:read-only:bg-gray-800
                     read-only:text-gray-600 dark:read-only:text-gray-400 read-only:cursor-default" />
          </div>

        </div>

        <p v-if="!fiche && computedWorkdays.length" class="mt-2 text-xs text-gray-400 dark:text-gray-500">
          {{ computedWorkdays.length }} jours ouvrables sur cette période
        </p>

        <!-- Actions : création -->
        <div v-if="!fiche" class="mt-4 flex items-center justify-end gap-3">
          <p v-if="!projet.trim()" class="text-xs text-gray-400 dark:text-gray-500">
            Renseignez le projet pour continuer
          </p>
          <button
            @click="createFiche"
            :disabled="!projet.trim() || !localStart || !localEnd"
            class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium
                   hover:bg-indigo-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
          >
            Créer la fiche
          </button>
        </div>

        <!-- Actions : édition -->
        <div v-if="fiche && isEditing" class="mt-4 flex items-center justify-end gap-2">
          <button
            @click="cancelEdit"
            class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400
                   border border-gray-200 dark:border-gray-600 rounded-lg
                   hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            Annuler
          </button>
          <button
            @click="updateFiche"
            :disabled="isSaving"
            class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium
                   hover:bg-indigo-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed
                   flex items-center gap-2"
          >
            <svg v-if="isSaving" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
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
          <div
            v-else
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                   rounded-xl p-10 text-center"
          >
            <div class="w-10 h-10 bg-gray-50 dark:bg-gray-700 rounded-xl flex items-center justify-center mx-auto mb-3">
              <svg class="w-5 h-5 text-gray-300 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
            <p class="text-sm text-gray-400 dark:text-gray-500 leading-relaxed">
              Sélectionnez un jour<br>ouvrable dans le calendrier
            </p>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active { transition: all 0.2s ease; }
.slide-down-enter-from,
.slide-down-leave-to    { opacity: 0; transform: translateY(-6px); }
</style>
