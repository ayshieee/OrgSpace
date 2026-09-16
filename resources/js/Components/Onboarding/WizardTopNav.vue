<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon, QuestionMarkCircleIcon, Cog6ToothIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    current: { type: String, required: true },
    breadcrumbs: { type: Array, default: null },
});

const steps = [
    { key: 'profile', label: 'Profile' },
    { key: 'roles', label: 'Roles & Permissions' },
    { key: 'members', label: 'Members' },
    { key: 'features', label: 'Features' },
    { key: 'review', label: 'Review' },
];

const currentIndex = computed(() => steps.findIndex((s) => s.key === props.current));

function isReached(index) {
    return index <= currentIndex.value;
}
</script>

<template>
    <header class="bg-white border-b border-neutral-200">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-8">
                <Link :href="route('onboarding.profile.show')" class="font-heading font-extrabold text-xl text-primary shrink-0">OrgSpace</Link>
                <nav class="hidden md:flex items-center gap-6">
                    <template v-for="(step, index) in steps" :key="step.key">
                        <Link
                            v-if="isReached(index)"
                            :href="route(`onboarding.${step.key}.show`)"
                            class="text-sm pb-1 border-b-2 transition-colors"
                            :class="index === currentIndex
                                ? 'font-semibold text-primary border-primary'
                                : 'font-medium text-tertiary-500 border-transparent hover:text-tertiary-700'"
                        >
                            {{ step.label }}
                        </Link>
                        <span v-else class="text-sm font-medium text-tertiary-300 pb-1 border-b-2 border-transparent cursor-not-allowed">
                            {{ step.label }}
                        </span>
                    </template>
                </nav>
            </div>

            <div class="flex items-center gap-1 shrink-0">
                <button type="button" title="Help" class="p-2 rounded-md text-tertiary-400 hover:text-tertiary-600 hover:bg-neutral-100">
                    <QuestionMarkCircleIcon class="w-5 h-5" />
                </button>
                <button type="button" title="Settings" class="p-2 rounded-md text-tertiary-400 hover:text-tertiary-600 hover:bg-neutral-100">
                    <Cog6ToothIcon class="w-5 h-5" />
                </button>
            </div>
        </div>

        <div v-if="breadcrumbs" class="border-t border-neutral-100 bg-neutral-50">
            <div class="max-w-6xl mx-auto px-6 py-2 flex items-center gap-1.5 text-xs">
                <template v-for="(crumb, index) in breadcrumbs" :key="index">
                    <span :class="index === breadcrumbs.length - 1 ? 'font-semibold text-tertiary-700' : 'text-tertiary-400'">{{ crumb }}</span>
                    <ChevronRightIcon v-if="index < breadcrumbs.length - 1" class="w-3 h-3 text-tertiary-300" />
                </template>
            </div>
        </div>
    </header>
</template>
