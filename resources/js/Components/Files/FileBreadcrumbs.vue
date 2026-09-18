<script setup>
import { ChevronRightIcon } from '@heroicons/vue/24/outline';

defineProps({
    breadcrumbs: { type: Array, default: () => [] },
});

const emit = defineEmits(['navigate']);
</script>

<template>
    <nav class="flex items-center flex-wrap gap-1 text-sm mb-4">
        <button type="button" @click="emit('navigate', null)"
            class="font-semibold transition-colors duration-150"
            :class="breadcrumbs.length ? 'text-tertiary-400 hover:text-primary-600' : 'text-tertiary-900'">
            Files
        </button>
        <template v-for="(crumb, index) in breadcrumbs" :key="crumb.id">
            <ChevronRightIcon class="w-3.5 h-3.5 text-tertiary-300 shrink-0" />
            <button type="button" @click="emit('navigate', crumb.id)"
                class="font-semibold transition-colors duration-150 truncate max-w-[200px]"
                :class="index === breadcrumbs.length - 1 ? 'text-tertiary-900' : 'text-tertiary-400 hover:text-primary-600'">
                {{ crumb.name }}
            </button>
        </template>
    </nav>
</template>
