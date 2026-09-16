<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <button
        type="button"
        :disabled="disabled"
        @click="$emit('update:modelValue', !modelValue)"
        class="shrink-0 relative w-11 h-6 rounded-full transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
        :class="modelValue ? 'bg-primary' : 'bg-neutral-300'"
    >
        <span
            class="absolute top-[2px] left-[2px] w-5 h-5 bg-white rounded-full shadow-sm transition-transform"
            :class="modelValue ? 'translate-x-5' : 'translate-x-0'"
        ></span>
    </button>
</template>
