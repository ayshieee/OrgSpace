<script setup>
defineProps({
    icon: { type: [Object, Function], required: true },
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    caption: { type: String, default: '' },
    tone: { type: String, default: 'primary' }, // primary | secondary | neutral
});

const toneClasses = {
    primary: { icon: 'bg-primary-50 text-primary-600 group-hover:bg-primary-600 group-hover:text-white', value: 'text-primary-700', caption: 'text-primary-500' },
    secondary: { icon: 'bg-secondary-50 text-secondary-700 group-hover:bg-secondary-400 group-hover:text-tertiary-900', value: 'text-tertiary-900', caption: 'text-secondary-700' },
    neutral: { icon: 'bg-neutral-100 text-tertiary-500 group-hover:bg-primary-600 group-hover:text-white', value: 'text-tertiary-900', caption: 'text-tertiary-400' },
};
</script>

<template>
    <div
        class="group relative overflow-hidden bg-white border border-neutral-200/60 rounded-2xl shadow-soft p-5
            transition-all duration-500 ease-ios
            hover:-translate-y-1 hover:scale-[1.015] hover:border-primary-300/60
            hover:shadow-[0_24px_48px_-18px_rgba(46,65,141,0.35)]
            hover:bg-gradient-to-br hover:from-primary-50/70 hover:via-white hover:to-secondary-50/50"
    >
        <!-- Ambient glass glow -->
        <div class="pointer-events-none absolute -top-10 -right-10 w-32 h-32 rounded-full bg-primary-500/0 group-hover:bg-primary-500/15 blur-2xl transition-all duration-500 ease-ios"></div>
        <div class="pointer-events-none absolute -bottom-12 -left-8 w-28 h-28 rounded-full bg-secondary-400/0 group-hover:bg-secondary-400/15 blur-2xl transition-all duration-500 ease-ios"></div>

        <!-- Top accent sheen -->
        <span class="pointer-events-none absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-primary-400 via-secondary-400 to-primary-400 scale-x-0 group-hover:scale-x-100 origin-left transition-transform duration-500 ease-ios"></span>

        <div class="relative z-10">
            <div class="flex items-start justify-between gap-3">
                <p class="text-[11px] font-bold uppercase tracking-wider text-tertiary-400 leading-snug transition-colors duration-300 ease-ios group-hover:text-primary-600">{{ label }}</p>
                <div
                    class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 transition-all duration-300 ease-ios group-hover:scale-110 group-hover:shadow-elevated"
                    :class="toneClasses[tone].icon"
                >
                    <component :is="icon" class="w-5 h-5" />
                </div>
            </div>
            <p class="text-2xl font-heading font-extrabold mt-2.5 transition-transform duration-300 ease-ios group-hover:scale-105 origin-left" :class="toneClasses[tone].value">{{ value }}</p>
            <p v-if="caption" class="text-xs mt-1" :class="toneClasses[tone].caption">{{ caption }}</p>
        </div>
    </div>
</template>
