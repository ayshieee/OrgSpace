<script setup>
import StepProgress from '@/Components/Onboarding/StepProgress.vue';

defineProps({
    step: { type: String, default: null },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-neutral-50 via-primary-50 to-neutral-100 py-10 px-4 sm:px-6 lg:px-8 font-sans">
        <div class="max-w-3xl mx-auto">
            <div class="flex justify-center items-center mb-8">
                <span class="font-heading font-extrabold text-xl tracking-tight text-primary">OrgSpace</span>
            </div>

            <StepProgress v-if="step" :current="step" />

            <div class="bg-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.08)] border border-neutral-200 p-6 sm:p-10">
                <div class="mb-8">
                    <h1 class="font-heading text-2xl font-extrabold text-tertiary-900 tracking-tight">{{ title }}</h1>
                    <p v-if="subtitle" class="mt-1.5 text-sm text-tertiary-500">{{ subtitle }}</p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
