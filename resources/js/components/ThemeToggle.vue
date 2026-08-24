<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(defineProps<{ class?: string }>(), { class: '' });

const { appearance, updateAppearance } = useAppearance();

const themeIcons = { light: Sun, dark: Moon, system: Monitor };
const themeLabels = { light: 'Clair', dark: 'Sombre', system: 'Système' };

const currentIcon = computed(() => themeIcons[appearance.value]);
const nextLabel = computed(() => {
    const order = ['light', 'dark', 'system'] as const;
    const next = order[(order.indexOf(appearance.value) + 1) % order.length];
    return themeLabels[next];
});

function cycleTheme() {
    const order = ['light', 'dark', 'system'] as const;
    updateAppearance(order[(order.indexOf(appearance.value) + 1) % order.length]);
}
</script>

<template>
    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger as-child>
                <Button variant="ghost" size="icon" :class="props.class" @click="cycleTheme">
                    <component :is="currentIcon" class="h-4 w-4" />
                    <span class="sr-only">Changer le thème ({{ themeLabels[appearance] }})</span>
                </Button>
            </TooltipTrigger>
            <TooltipContent>Thème : {{ themeLabels[appearance] }} — cliquer pour passer à {{ nextLabel }}</TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
