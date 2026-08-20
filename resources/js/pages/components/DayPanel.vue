<script setup>
import { ref, watch, nextTick, computed } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/fr'
import { FileSpreadsheet, GitBranch, List } from 'lucide-vue-next'
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

dayjs.locale('fr')

const props = defineProps({
  day:         String,
  entryId:     Number,
  tasks:       Array,
  comment:     String,
  projet:      String,
  ficheProjet: String,
  saving:      Boolean,
  ficheExists: Boolean,
})
const emit = defineEmits(['save'])

const localTasks   = ref([...props.tasks])
const localComment = ref(props.comment ?? '')
const localProjet  = ref(props.projet ?? '')

watch(() => props.tasks,   t => { localTasks.value = [...t] })
watch(() => props.comment, c => { localComment.value = c ?? '' })
watch(() => props.projet,  p => { localProjet.value = p ?? '' })
watch(() => props.day,     () => {
  localTasks.value   = [...props.tasks]
  localComment.value = props.comment ?? ''
  localProjet.value  = props.projet ?? ''
})

const dateLabel = () => dayjs(props.day).format('dddd D MMMM')

async function addTask(atStart = false) {
  if (atStart) {
    localTasks.value.unshift('')
  } else {
    localTasks.value.push('')
  }
  await nextTick()
  const items = document.querySelectorAll('.task-item')
  const target = atStart ? items[0] : items[items.length - 1]
  target?.focus()
}

function removeTask(i) {
  localTasks.value.splice(i, 1)
}

function onSave() {
    emit('save', props.day, props.entryId, localTasks.value.filter(t => t.trim()), localComment.value.trim(), localProjet.value.trim())
    toast.success('Fiche sauvegardée avec succès !');
}

// ── Vue Excel ─────────────────────────────────────────────────────────────────
const showExcel   = ref(false)
const excelCopied = ref(false)

const excelText = computed(() =>
    localTasks.value
        .filter(t => t.trim())
        .map((t, i) => `${i + 1}. ${t.trim()}`)
        .join('\n')
)

async function copyForExcel() {
    if (!excelText.value) return
    await navigator.clipboard.writeText(excelText.value)
    excelCopied.value = true
    setTimeout(() => (excelCopied.value = false), 2000)
}

// ── Saisie rapide (bulk) ──────────────────────────────────────────────────────
const showBulk  = ref(false)
const bulkText  = ref('')

function openBulk() {
    bulkText.value = localTasks.value
        .filter(t => t.trim())
        .map((t, i) => `${i + 1}. ${t.trim()}`)
        .join('\n')
    showBulk.value = !showBulk.value
}

function importBulk() {
    const lines = parseTaskLines(bulkText.value)
    if (!lines.length) return
    localTasks.value = lines
    showBulk.value   = false
    toast.success(`${lines.length} tâche${lines.length > 1 ? 's' : ''} importée${lines.length > 1 ? 's' : ''}`)
}

// ── Git → inject ──────────────────────────────────────────────────────────────
const gitOpen     = ref(false)
const gitProjects = ref([])
const gitProject  = ref('')
const gitLoading  = ref(false)
const gitError    = ref('')

function getCsrf() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)
    return match ? decodeURIComponent(match[1]) : ''
}

async function toggleGit() {
    gitError.value = ''
    gitOpen.value  = !gitOpen.value
    if (gitOpen.value && !gitProjects.value.length) {
        try {
            const res  = await fetch('/git-projects', { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            const data = await res.json()
            gitProjects.value = data.projects ?? []
            gitProject.value  = gitProjects.value[0] ?? ''
        } catch { /* silent */ }
    }
}

function parseTaskLines(text) {
    return text
        .split('\n')
        .filter(line => /^\d+[\.\)]\s/.test(line.trim()))
        .map(line => line.replace(/^\d+[\.\)]\s+/, '').trim())
        .filter(Boolean)
}

async function generateFromGit() {
    if (!gitProject.value) return
    gitError.value   = ''
    gitLoading.value = true
    try {
        const res  = await fetch('/git-to-timesheet', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': getCsrf() },
            body: JSON.stringify({ date: props.day, project: gitProject.value }),
        })
        const data = await res.json()
        if (!res.ok) {
            gitError.value = data.error ?? 'Une erreur est survenue.'
        } else if (data.empty) {
            gitError.value = 'Aucun commit trouvé pour ce jour.'
        } else {
            const lines = parseTaskLines(data.tasks ?? '')
            if (lines.length) {
                localTasks.value = lines
                gitOpen.value    = false
                toast.success(`${lines.length} tâche${lines.length > 1 ? 's' : ''} injectée${lines.length > 1 ? 's' : ''} depuis Git`)
            } else {
                gitError.value = 'Aucune tâche extraite de la réponse.'
            }
        }
    } catch {
        gitError.value = 'Erreur réseau.'
    } finally {
        gitLoading.value = false
    }
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
               rounded-xl overflow-hidden sticky top-20">

    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700
                bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between">
      <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 capitalize">
        {{ dateLabel() }}
      </h3>
      <span v-if="entryId" class="text-[10px] font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
        </svg>
        Enregistré
      </span>
    </div>

    <div class="p-4 space-y-4">

      <!-- Project override -->
      <div>
        <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
          Projet
        </p>
        <input
          v-model="localProjet"
          type="text"
          :placeholder="ficheProjet || 'Nom du projet'"
          title="Laissez vide pour utiliser le projet par défaut de la fiche"
          class="w-full rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm text-gray-800
                 placeholder-gray-300 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500
                 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-600"
        />
      </div>

      <!-- Task list -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <p class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
            Tâches
            <span class="font-normal text-gray-300 dark:text-gray-600">({{ localTasks.length }})</span>
          </p>
          <div class="flex items-center gap-2">
            <button
              @click="toggleGit"
              :class="gitOpen
                ? 'text-indigo-600 dark:text-indigo-400'
                : 'text-gray-400 dark:text-gray-500 hover:text-indigo-500 dark:hover:text-indigo-400'"
              class="flex items-center gap-1 text-[10px] font-medium transition-colors"
              title="Générer depuis Git"
            >
              <GitBranch class="w-3 h-3" />
              Git
            </button>
            <span class="text-gray-200 dark:text-gray-700">|</span>
            <button
              @click="openBulk"
              :class="showBulk
                ? 'text-violet-600 dark:text-violet-400'
                : 'text-gray-400 dark:text-gray-500 hover:text-violet-500 dark:hover:text-violet-400'"
              class="flex items-center gap-1 text-[10px] font-medium transition-colors"
              title="Saisie rapide (liste numérotée)"
            >
              <List class="w-3 h-3" />
              Liste
            </button>
            <span class="text-gray-200 dark:text-gray-700">|</span>
            <button
              @click="showExcel = !showExcel"
              :class="showExcel
                ? 'text-emerald-600 dark:text-emerald-400'
                : 'text-gray-400 dark:text-gray-500 hover:text-emerald-600 dark:hover:text-emerald-400'"
              class="flex items-center gap-1 text-[10px] font-medium transition-colors"
              title="Vue cellule Excel"
            >
              <FileSpreadsheet class="w-3 h-3" />
              Excel
            </button>
            <span class="text-gray-200 dark:text-gray-700">|</span>
            <button
              @click="addTask(true)"
              title="Ajouter une tâche en début de liste"
              class="text-[10px] font-medium text-indigo-500 dark:text-indigo-400
                     hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors"
            >
              + Début
            </button>
            <span class="text-gray-200 dark:text-gray-700">|</span>
            <button
              @click="addTask()"
              title="Ajouter une tâche en fin de liste"
              class="text-[10px] font-medium text-indigo-500 dark:text-indigo-400
                     hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors"
            >
              + Ajouter
            </button>
          </div>
        </div>

        <!-- Git generate panel -->
        <div v-if="gitOpen" class="mb-3 rounded-lg border border-indigo-100 dark:border-indigo-900/50 bg-indigo-50/60 dark:bg-indigo-950/20 p-3 space-y-2">
          <select
            v-model="gitProject"
            class="w-full rounded-md border border-indigo-200 dark:border-indigo-800 bg-white dark:bg-gray-700 px-2.5 py-1.5 text-xs text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-400"
          >
            <option v-if="!gitProjects.length" value="" disabled>Chargement…</option>
            <option v-for="p in gitProjects" :key="p" :value="p">{{ p }}</option>
          </select>
          <button
            @click="generateFromGit"
            :disabled="gitLoading || !gitProject"
            class="w-full flex items-center justify-center gap-1.5 rounded-md bg-indigo-600 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <svg v-if="gitLoading" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <GitBranch v-else class="w-3 h-3" />
            {{ gitLoading ? 'Génération…' : 'Générer les tâches' }}
          </button>
          <p v-if="gitError" class="text-[10px] text-red-500 dark:text-red-400">{{ gitError }}</p>
        </div>

        <!-- Bulk input panel -->
        <div v-if="showBulk" class="mb-3 rounded-lg border border-violet-100 dark:border-violet-900/50 bg-violet-50/60 dark:bg-violet-950/20 p-3 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-semibold text-violet-700 dark:text-violet-400 uppercase tracking-wider flex items-center gap-1">
              <List class="w-3 h-3" />
              Saisie rapide
            </span>
            <span class="text-[10px] text-violet-500/70 dark:text-violet-400/60">Format : 1. tâche, 2. tâche…</span>
          </div>
          <textarea
            v-model="bulkText"
            :rows="Math.max(4, bulkText.split('\n').length + 1)"
            placeholder="1. Première tâche&#10;2. Deuxième tâche&#10;3. Troisième tâche"
            class="w-full resize-none rounded-md border border-violet-200 dark:border-violet-800 bg-white dark:bg-gray-700 px-2.5 py-2 text-xs text-gray-800 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-violet-400 font-mono"
          />
          <button
            @click="importBulk"
            :disabled="!bulkText.trim()"
            class="w-full flex items-center justify-center gap-1.5 rounded-md bg-violet-600 py-1.5 text-xs font-medium text-white hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <List class="w-3 h-3" />
            Importer les tâches
          </button>
        </div>

        <!-- Excel cell view -->
        <div v-if="showExcel && localTasks.filter(t => t.trim()).length"
             class="mb-3 rounded-lg border border-emerald-100 dark:border-emerald-900/50 bg-emerald-50/60 dark:bg-emerald-950/20 p-3 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider flex items-center gap-1">
              <FileSpreadsheet class="w-3 h-3" />
              Cellule Excel
            </span>
            <button
              @click="copyForExcel"
              class="flex items-center gap-1 rounded px-2 py-0.5 text-[10px] font-medium transition-colors"
              :class="excelCopied
                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
                : 'text-emerald-600 hover:bg-emerald-100 dark:text-emerald-400 dark:hover:bg-emerald-900/30'"
            >
              <svg v-if="excelCopied" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
              </svg>
              <svg v-else class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              {{ excelCopied ? 'Copié !' : 'Copier' }}
            </button>
          </div>
          <textarea
            readonly
            :value="excelText"
            :rows="localTasks.filter(t => t.trim()).length + 1"
            class="w-full resize-none rounded-md border border-emerald-200 dark:border-emerald-800 bg-white dark:bg-gray-700 px-2.5 py-2 font-mono text-xs text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-emerald-400 select-all"
            @click="$event.target.select()"
          />
          <p class="text-[10px] text-emerald-600/70 dark:text-emerald-500/70">
            Double-cliquez dans une cellule Excel, puis collez (Ctrl+V).
          </p>
        </div>

        <div v-if="localTasks.length" class="space-y-1.5">
          <div
            v-for="(_, i) in localTasks" :key="i"
            class="flex gap-2 items-center"
          >
            <span class="text-gray-300 dark:text-gray-600 text-[11px] shrink-0 w-4 text-right tabular-nums">
              {{ i + 1 }}.
            </span>
            <input
              v-model="localTasks[i]"
              type="text"
              placeholder="Décrivez la tâche…"
              class="task-item flex-1 border border-gray-200 dark:border-gray-600 rounded-lg px-2.5 py-1.5
                     text-sm bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200
                     placeholder-gray-300 dark:placeholder-gray-600
                     focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            />
            <button
              @click="removeTask(i)"
              class="text-gray-300 dark:text-gray-600 hover:text-red-400 dark:hover:text-red-400
                     shrink-0 text-base leading-none transition-colors"
            >×</button>
          </div>
        </div>

        <div v-else class="py-4 text-center">
          <p class="text-xs text-gray-400 dark:text-gray-500 mb-3">Aucune tâche pour ce jour</p>
          <button
            @click="addTask"
            class="px-4 py-2 text-xs font-medium text-indigo-600 dark:text-indigo-400
                   border border-indigo-200 dark:border-indigo-700 rounded-lg
                   hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors"
          >
            + Ajouter une tâche
          </button>
        </div>
      </div>

      <!-- Comment -->
      <div>
        <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
          Commentaire
        </p>
        <textarea
          v-model="localComment"
          rows="3"
          placeholder="Observations, blocages, notes…"
          class="w-full resize-none rounded-lg border border-gray-200 bg-white px-2.5 py-2 text-sm text-gray-800 placeholder-gray-300 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-600"
        />
      </div>

      <!-- Save -->
      <template v-if="ficheExists">
        <button
          @click="onSave"
          :disabled="saving"
          class="w-full py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg
                 hover:bg-indigo-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed
                 flex items-center justify-center gap-2"
        >
          <svg v-if="saving" class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          {{ saving ? 'Sauvegarde…' : 'Sauvegarder' }}
        </button>
      </template>
      <p v-else class="text-[10px] text-amber-600 dark:text-amber-400 text-center">
        Créez d'abord la fiche pour sauvegarder
      </p>

    </div>
  </div>
</template>
