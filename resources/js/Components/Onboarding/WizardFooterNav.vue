<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

defineProps({
    backRoute: { type: String, default: null },
});
</script>

<template>
    <div class="pt-6 mt-8 border-t border-neutral-200 flex items-center justify-between">
        <Link v-if="backRoute" :href="backRoute" class="inline-flex items-center gap-1.5 text-sm font-semibold text-tertiary-500 hover:text-tertiary-700">
            <ArrowLeftIcon class="w-4 h-4" /> Back
        </Link>
        <span v-else></span>

        <div class="flex items-center gap-4">
            <slot name="secondary" />
            <slot name="primary" />
        </div>
    </div>
</template>
