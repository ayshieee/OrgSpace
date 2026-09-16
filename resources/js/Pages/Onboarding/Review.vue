<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PencilSquareIcon, RocketLaunchIcon } from '@heroicons/vue/24/outline';
import WizardWorkspaceLayout from '@/Layouts/WizardWorkspaceLayout.vue';
import WizardFooterNav from '@/Components/Onboarding/WizardFooterNav.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    permissionKeys: { type: Object, required: true },
    moduleCatalog: { type: Object, required: true },
    orgTypes: { type: Object, required: true },
});

const nonSystemRoles = computed(() => props.organization.roles.filter((r) => !r.is_system));
const enabledFeatures = computed(() => props.organization.features.filter((f) => f.is_enabled));

const form = useForm({});
function activate() {
    form.post(route('onboarding.activate'));
}
</script>

<template>
    <Head title="Review & Activate" />

    <WizardWorkspaceLayout step="review" content-class="max-w-2xl">
        <div class="text-center mb-8">
            <h1 class="font-heading text-2xl font-extrabold text-tertiary-900 tracking-tight">Review & Activate</h1>
            <p class="mt-1.5 text-sm text-tertiary-500">Verify your organization's configuration before finalizing setup.</p>
        </div>

        <div class="space-y-5">
            <!-- Profile -->
            <section class="border border-neutral-200 rounded-xl bg-white p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-heading font-bold text-tertiary-900">Organization Profile</h3>
                    <Link :href="route('onboarding.profile.show', { from: 'review' })" class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:text-primary-700">
                        <PencilSquareIcon class="w-4 h-4" /> Edit
                    </Link>
                </div>
                <div class="flex items-center gap-4">
                    <img v-if="organization.logo_path" :src="`/storage/${organization.logo_path}`" class="w-12 h-12 rounded-lg object-cover border border-neutral-200" />
                    <div>
                        <p class="font-semibold text-tertiary-900">{{ organization.name }}</p>
                        <p class="text-sm text-tertiary-500">{{ orgTypes[organization.type] }}</p>
                    </div>
                </div>
                <p v-if="organization.description" class="mt-2 text-sm text-tertiary-500">{{ organization.description }}</p>
            </section>

            <!-- Roles -->
            <section class="border border-neutral-200 rounded-xl bg-white p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-heading font-bold text-tertiary-900">Roles & Structure</h3>
                    <Link :href="route('onboarding.roles.show', { from: 'review' })" class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:text-primary-700">
                        <PencilSquareIcon class="w-4 h-4" /> Edit
                    </Link>
                </div>
                <div class="space-y-2">
                    <div v-for="role in nonSystemRoles" :key="role.id" class="flex items-center justify-between px-3 py-2 rounded-lg bg-neutral-50">
                        <span class="text-sm font-medium text-tertiary-800">{{ role.name }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white border border-neutral-200 text-tertiary-500">
                            {{ role.permissions.length }} permission{{ role.permissions.length === 1 ? '' : 's' }}
                        </span>
                    </div>
                </div>
            </section>

            <!-- Members -->
            <section class="border border-neutral-200 rounded-xl bg-white p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-heading font-bold text-tertiary-900">Members</h3>
                    <Link :href="route('onboarding.members.show', { from: 'review' })" class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:text-primary-700">
                        <PencilSquareIcon class="w-4 h-4" /> Edit
                    </Link>
                </div>
                <p class="text-sm text-tertiary-600">
                    {{ organization.members.length }} member{{ organization.members.length === 1 ? '' : 's' }} added
                    <span v-if="organization.members.length === 0" class="text-tertiary-400">&mdash; you can add more later from the Members page.</span>
                </p>
            </section>

            <!-- Features -->
            <section class="border border-neutral-200 rounded-xl bg-white p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-heading font-bold text-tertiary-900">Active Modules</h3>
                    <Link :href="route('onboarding.features.show', { from: 'review' })" class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:text-primary-700">
                        <PencilSquareIcon class="w-4 h-4" /> Edit
                    </Link>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span v-for="feature in enabledFeatures" :key="feature.id" class="px-3 py-1 rounded-full text-xs font-semibold bg-primary-50 text-primary-700">
                        {{ moduleCatalog[feature.module_key]?.name ?? feature.module_key }}
                    </span>
                </div>
            </section>
        </div>

        <WizardFooterNav :back-route="route('onboarding.features.show')">
            <template #primary>
                <PrimaryButton :disabled="form.processing" @click="activate">
                    <RocketLaunchIcon class="w-4 h-4 mr-1.5" />
                    {{ form.processing ? 'Activating…' : 'Activate Organization' }}
                </PrimaryButton>
            </template>
        </WizardFooterNav>
    </WizardWorkspaceLayout>
</template>
