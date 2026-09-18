<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-lg border border-secondary-400 bg-secondary-50 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-secondary-800 shadow-sm transition-all duration-200 ease-ios hover:bg-secondary-100 hover:border-secondary-500 hover:shadow-soft focus:outline-none focus:ring-2 focus:ring-secondary-500 focus:ring-offset-2 disabled:opacity-40"
    >
        <slot />
    </button>
</template>
