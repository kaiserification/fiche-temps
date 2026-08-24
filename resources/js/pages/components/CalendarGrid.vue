<script setup lang="ts">
import dayjs, { type Dayjs } from 'dayjs';
import 'dayjs/locale/fr';

dayjs.locale('fr');

const props = defineProps<{
    workdays: string[];
    filledDays: Record<string, unknown>;
    selectedDay?: string | null;
    periodStart: string;
    periodEnd: string;
}>();
const emit = defineEmits<{ (e: 'select', day: string): void }>();

const MONTHS_FR = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
const DAYS_FR = ['Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa', 'Di'];

function getMonths() {
    const start = dayjs(props.periodStart);
    const end = dayjs(props.periodEnd);
    const months: Dayjs[] = [];
    let cur = start.startOf('month');
    while (cur.isBefore(end.endOf('month'))) {
        months.push(cur);
        cur = cur.add(1, 'month');
    }
    return months;
}

function getDaysInMonth(month: Dayjs) {
    const days: (Dayjs | null)[] = [];
    const first = month.startOf('month');
    const last = month.endOf('month');
    const startWd = (first.day() + 6) % 7;
    for (let i = 0; i < startWd; i++) days.push(null);
    for (let d = 1; d <= last.date(); d++) days.push(month.date(d));
    return days;
}

function isWorkday(d: Dayjs) {
    return props.workdays.includes(d.format('YYYY-MM-DD'));
}
function isFilled(d: Dayjs) {
    const t = props.filledDays[d.format('YYYY-MM-DD')];
    return Array.isArray(t) && t.length > 0;
}
function isSelected(d: Dayjs) {
    return props.selectedDay === d.format('YYYY-MM-DD');
}
function isToday(d: Dayjs) {
    return d.format('YYYY-MM-DD') === dayjs().format('YYYY-MM-DD');
}
function isWeekend(d: Dayjs) {
    return d.day() === 0 || d.day() === 6;
}

function dayClasses(day: Dayjs | null) {
    if (!day) return '';
    if (isSelected(day)) return 'bg-primary text-primary-foreground font-semibold cursor-pointer';
    if (isFilled(day)) return 'bg-success-muted text-success font-medium cursor-pointer hover:brightness-95 transition-colors';
    if (!isWorkday(day)) return 'text-muted-foreground/40 cursor-default';
    if (isWeekend(day)) return 'text-warning hover:bg-warning-muted cursor-pointer transition-colors';
    return 'text-muted-foreground hover:bg-primary/10 hover:text-primary cursor-pointer transition-colors';
}

function select(d: Dayjs | null) {
    if (!d || !isWorkday(d)) return;
    emit('select', d.format('YYYY-MM-DD'));
}
</script>

<template>
    <div class="space-y-4">
        <!-- Legend -->
        <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-[11px] text-muted-foreground">
            <span class="flex items-center gap-1.5">
                <span class="inline-block h-3.5 w-3.5 shrink-0 rounded border border-success/30 bg-success-muted"></span>
                Rempli
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block h-3.5 w-3.5 shrink-0 rounded bg-primary"></span>
                Sélectionné
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block h-3.5 w-3.5 shrink-0 rounded border-2 border-primary bg-card"></span>
                Aujourd'hui
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block h-3.5 w-3.5 shrink-0 rounded border border-border bg-card"></span>
                Jour ouvrable
            </span>
            <span class="flex items-center gap-1.5 text-warning">
                <span class="inline-block h-3.5 w-3.5 shrink-0 rounded border border-warning/40 bg-card"></span>
                Week-end exceptionnel
            </span>
        </div>

        <!-- Months grid -->
        <div class="grid grid-cols-2 gap-4 xl:grid-cols-3">
            <div v-for="month in getMonths()" :key="month.format('YYYY-MM')" class="rounded-xl border border-border bg-card p-4 shadow-soft-sm">
                <!-- Month header -->
                <p class="mb-3 text-xs font-semibold text-foreground">
                    {{ MONTHS_FR[month.month()] }}
                    <span class="ml-0.5 font-normal text-muted-foreground">{{ month.year() }}</span>
                </p>

                <!-- Day-of-week headers -->
                <div class="mb-2 grid grid-cols-7">
                    <span v-for="d in DAYS_FR" :key="d" class="text-center text-[9px] font-semibold uppercase tracking-wide text-muted-foreground">{{
                        d
                    }}</span>
                </div>

                <!-- Days grid -->
                <div class="grid grid-cols-7 gap-0.5">
                    <div
                        v-for="(day, i) in getDaysInMonth(month)"
                        :key="i"
                        :class="[
                            'rounded-md py-1 text-center text-xs leading-4',
                            dayClasses(day),
                            day && isToday(day) && !isSelected(day) ? 'ring-2 ring-primary ring-offset-1 ring-offset-card' : '',
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
