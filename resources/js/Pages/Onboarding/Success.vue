<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CheckIcon } from '@heroicons/vue/24/outline';

defineProps({
    organizationName: { type: String, required: true },
});
</script>

<template>
    <Head title="Organization Ready" />

    <div class="min-h-screen bg-gradient-to-br from-neutral-50 via-primary-50 to-neutral-100 flex flex-col items-center justify-center px-4 font-sans">
        <div class="w-full max-w-sm bg-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.08)] border border-neutral-200 p-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-primary-50 flex items-center justify-center mb-5">
                <CheckIcon class="w-7 h-7 text-primary-600" />
            </div>
            <h1 class="font-heading text-xl font-extrabold text-tertiary-900">{{ organizationName }} is ready!</h1>
            <p class="mt-2 text-sm text-tertiary-500">
                Activation successful. You can now start managing your members, roles, and campus events.
            </p>
            <Link :href="route('dashboard')"
                class="mt-6 inline-flex items-center justify-center w-full rounded-lg border border-transparent bg-gradient-to-b from-secondary-400 to-secondary-500 px-4 py-2.5 text-sm font-semibold text-tertiary-900 shadow-soft transition-all duration-200 ease-ios hover:shadow-elevated hover:from-secondary-300 hover:to-secondary-400 active:scale-[0.98]">
                Go to Dashboard &rarr;
            </Link>
        </div>

        <span class="mt-6 font-heading font-extrabold text-lg text-primary">OrgSpace</span>
    </div>
</template>
