<script setup>
import WizardTopNav from '@/Components/Onboarding/WizardTopNav.vue';

defineProps({
    step: { type: String, required: true },
    breadcrumbs: { type: Array, default: null },
    contentClass: { type: String, default: 'max-w-4xl' },
});
</script>

<template>
    <div class="min-h-screen bg-neutral-50 font-sans">
        <WizardTopNav :current="step" :breadcrumbs="breadcrumbs" />

        <main class="mx-auto px-6 py-10" :class="contentClass">
            <slot />
        </main>
    </div>
</template>
