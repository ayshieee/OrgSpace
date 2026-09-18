<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircleIcon, ExclamationTriangleIcon, XCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const page = usePage();

const kinds = [
    { key: 'success', icon: CheckCircleIcon, classes: 'bg-emerald-50 border-emerald-200 text-emerald-800', iconClasses: 'text-emerald-500' },
    { key: 'warning', icon: ExclamationTriangleIcon, classes: 'bg-secondary-50 border-secondary-300 text-secondary-800', iconClasses: 'text-secondary-600' },
    { key: 'error', icon: XCircleIcon, classes: 'bg-red-50 border-red-200 text-red-800', iconClasses: 'text-red-500' },
];

const dismissed = ref(new Set());

// Flash props are session-flashed (one request only), but keep a local
// dismiss set keyed by message text so re-renders of the same page don't
// resurrect a banner the user already closed.
watch(() => page.props.flash, () => { dismissed.value = new Set(); });

const visible = computed(() => kinds
    .map((kind) => ({ ...kind, message: page.props.flash?.[kind.key] }))
    .filter((kind) => kind.message && !dismissed.value.has(kind.message)));

function dismiss(message) {
    dismissed.value = new Set([...dismissed.value, message]);
}
</script>

<template>
    <div v-if="visible.length" class="space-y-2 mb-4">
        <div v-for="kind in visible" :key="kind.key"
            class="flex items-start gap-2.5 rounded-xl border px-4 py-3 text-sm shadow-soft animate-fade-in-up"
            :class="kind.classes">
            <component :is="kind.icon" class="w-5 h-5 shrink-0 mt-0.5" :class="kind.iconClasses" />
            <p class="flex-1">{{ kind.message }}</p>
            <button type="button" @click="dismiss(kind.message)" class="shrink-0 opacity-60 hover:opacity-100 transition-opacity">
                <XMarkIcon class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>
