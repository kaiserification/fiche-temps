<script setup>
import dayjs from 'dayjs'
import 'dayjs/locale/fr'

dayjs.locale('fr')

const props = defineProps({
  workdays:    Array,
  filledDays:  Object,
  selectedDay: String,
  periodStart: String,
  periodEnd:   String,
})
const emit = defineEmits(['select'])

const MONTHS_FR = ['Janvier','Février','Mars','Avril','Mai','Juin',
                   'Juillet','Août','Septembre','Octobre','Novembre','Décembre']
const DAYS_FR   = ['Lu','Ma','Me','Je','Ve','Sa','Di']

function getMonths() {
  const start = dayjs(props.periodStart)
  const end   = dayjs(props.periodEnd)
  const months = []
  let cur = start.startOf('month')
  while (cur.isBefore(end.endOf('month'))) {
    months.push(cur)
    cur = cur.add(1, 'month')
  }
  return months
}

function getDaysInMonth(month) {
  const days    = []
  const first   = month.startOf('month')
  const last    = month.endOf('month')
  const startWd = (first.day() + 6) % 7
  for (let i = 0; i < startWd; i++) days.push(null)
  for (let d = 1; d <= last.date(); d++) days.push(month.date(d))
  return days
}

function isWorkday(d)  { return props.workdays.includes(d.format('YYYY-MM-DD')) }
function isFilled(d)   { const t = props.filledDays[d.format('YYYY-MM-DD')]; return Array.isArray(t) && t.length > 0 }
function isSelected(d) { return props.selectedDay === d.format('YYYY-MM-DD') }
function isToday(d)    { return d.format('YYYY-MM-DD') === dayjs().format('YYYY-MM-DD') }
function isWeekend(d)  { return d.day() === 0 || d.day() === 6 }

function dayClasses(day) {
  if (!day) return ''
  if (isSelected(day))
    return 'bg-indigo-600 text-white font-semibold cursor-pointer'
  if (isFilled(day))
    return 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-300 font-medium cursor-pointer hover:bg-emerald-200 dark:hover:bg-emerald-900/60 transition-colors'
  if (!isWorkday(day))
    return 'text-gray-200 dark:text-gray-700 cursor-default'
  if (isWeekend(day))
    return 'text-orange-400 dark:text-orange-500 hover:bg-orange-50 dark:hover:bg-orange-900/30 hover:text-orange-500 cursor-pointer transition-colors'
  return 'text-gray-600 dark:text-gray-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer transition-colors'
}

function select(d) {
  if (!d || !isWorkday(d)) return
  emit('select', d.format('YYYY-MM-DD'))
}
</script>

<template>
  <div class="space-y-4">

    <!-- Legend -->
    <div class="flex items-center gap-5 text-[11px] text-gray-500 dark:text-gray-400">
      <span class="flex items-center gap-1.5">
        <span class="w-3.5 h-3.5 rounded bg-emerald-100 dark:bg-emerald-900/40 border border-emerald-200 dark:border-emerald-800 inline-block shrink-0"></span>
        Rempli
      </span>
      <span class="flex items-center gap-1.5">
        <span class="w-3.5 h-3.5 rounded bg-indigo-600 inline-block shrink-0"></span>
        Sélectionné
      </span>
      <span class="flex items-center gap-1.5">
        <span class="w-3.5 h-3.5 rounded bg-white dark:bg-gray-800 border-2 border-indigo-400 inline-block shrink-0"></span>
        Aujourd'hui
      </span>
      <span class="flex items-center gap-1.5">
        <span class="w-3.5 h-3.5 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 inline-block shrink-0"></span>
        Jour ouvrable
      </span>
      <span class="flex items-center gap-1.5 text-orange-400 dark:text-orange-500">
        <span class="w-3.5 h-3.5 rounded bg-white dark:bg-gray-800 border border-orange-300 dark:border-orange-600 inline-block shrink-0"></span>
        Week-end exceptionnel
      </span>
    </div>

    <!-- Months grid -->
    <div class="grid grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="month in getMonths()"
        :key="month.format('YYYY-MM')"
        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4"
      >
        <!-- Month header -->
        <p class="text-xs font-semibold text-gray-800 dark:text-gray-200 mb-3">
          {{ MONTHS_FR[month.month()] }}
          <span class="text-gray-400 dark:text-gray-500 font-normal ml-0.5">{{ month.year() }}</span>
        </p>

        <!-- Day-of-week headers -->
        <div class="grid grid-cols-7 mb-2">
          <span
            v-for="d in DAYS_FR" :key="d"
            class="text-center text-[9px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide"
          >{{ d }}</span>
        </div>

        <!-- Days grid -->
        <div class="grid grid-cols-7 gap-0.5">
          <div
            v-for="(day, i) in getDaysInMonth(month)"
            :key="i"
            :class="[
              'text-center text-xs rounded-md py-1 leading-4',
              dayClasses(day),
              day && isToday(day) && !isSelected(day) ? 'ring-2 ring-indigo-400 ring-offset-1 dark:ring-offset-gray-800' : '',
            ]"
            @click="day && select(day)"
          >
            {{ day ? day.date() : '' }}
          </div>
        </div>
      </div>
    </div>

  </div>
</template>
