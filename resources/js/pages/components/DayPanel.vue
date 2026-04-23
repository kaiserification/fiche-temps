<script setup>
import { ref, watch, nextTick } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/fr'
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

dayjs.locale('fr')

const props = defineProps({
  day:         String,
  entryId:     Number,
  tasks:       Array,
  saving:      Boolean,
  ficheExists: Boolean,
})
const emit = defineEmits(['save'])

const localTasks = ref([...props.tasks])

watch(() => props.tasks, t => { localTasks.value = [...t] })
watch(() => props.day,   () => { localTasks.value = [...props.tasks] })

const dateLabel = () => dayjs(props.day).format('dddd D MMMM')

async function addTask() {
  localTasks.value.push('')
  await nextTick()
  const items = document.querySelectorAll('.task-item')
  items[items.length - 1]?.focus()
}

function removeTask(i) {
  localTasks.value.splice(i, 1)
}

function onSave() {
    emit('save', props.day, props.entryId, localTasks.value.filter(t => t.trim()))
    toast.success('Fiche sauvegardée avec succès !');
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

      <!-- Task list -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <p class="text-[10px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
            Tâches
            <span class="font-normal text-gray-300 dark:text-gray-600">({{ localTasks.length }})</span>
          </p>
          <button
            @click="addTask"
            class="text-[10px] font-medium text-indigo-500 dark:text-indigo-400
                   hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors"
          >
            + Ajouter
          </button>
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
