<script setup>
import { computed } from 'vue';
import { CheckIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    current: { type: String, required: true },
});

const steps = [
    { key: 'profile', label: 'Profile' },
    { key: 'roles', label: 'Roles' },
    { key: 'members', label: 'Members' },
    { key: 'features', label: 'Features' },
    { key: 'review', label: 'Review' },
];

const currentIndex = computed(() => steps.findIndex((s) => s.key === props.current));
</script>

<template>
    <ol class="flex items-center w-full max-w-2xl mx-auto mb-10">
        <li v-for="(step, index) in steps" :key="step.key" class="flex items-center" :class="index !== steps.length - 1 ? 'flex-1' : ''">
            <div class="flex flex-col items-center gap-1.5 shrink-0">
                <div
                    class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-colors"
                    :class="index <= currentIndex
                        ? 'bg-primary border-primary text-white'
                        : 'border-tertiary-200 text-tertiary-300 bg-white'"
                >
                    <CheckIcon v-if="index < currentIndex" class="w-4 h-4" />
                    <span v-else>{{ index + 1 }}</span>
                </div>
                <span
                    class="text-[11px] font-semibold uppercase tracking-wide whitespace-nowrap"
                    :class="index <= currentIndex ? 'text-tertiary-800' : 'text-tertiary-300'"
                >{{ step.label }}</span>
            </div>
            <div v-if="index !== steps.length - 1" class="flex-1 h-0.5 mx-2 -mt-5" :class="index < currentIndex ? 'bg-primary' : 'bg-tertiary-200'"></div>
        </li>
    </ol>
</template>
