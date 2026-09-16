<script setup>
defineProps({
    icon: { type: [Object, Function], required: true },
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    caption: { type: String, default: '' },
    tone: { type: String, default: 'primary' }, // primary | secondary | neutral
});

const toneClasses = {
    primary: 'bg-primary-50 text-primary-600',
    secondary: 'bg-secondary-50 text-secondary-700',
    neutral: 'bg-neutral-100 text-tertiary-500',
};
</script>

<template>
    <div class="bg-white border border-neutral-200 rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center" :class="toneClasses[tone]">
                <component :is="icon" class="w-5 h-5" />
            </div>
        </div>
        <p class="text-2xl font-heading font-extrabold text-tertiary-900">{{ value }}</p>
        <p class="text-sm font-semibold text-tertiary-700 mt-0.5">{{ label }}</p>
        <p v-if="caption" class="text-xs text-tertiary-400 mt-1">{{ caption }}</p>
    </div>
</template>
