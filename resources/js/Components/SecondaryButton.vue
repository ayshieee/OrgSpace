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
        class="inline-flex items-center rounded-md border border-tertiary-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-tertiary-700 shadow-sm transition duration-150 ease-in-out hover:bg-neutral-100 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-25"
    >
        <slot />
    </button>
</template>
