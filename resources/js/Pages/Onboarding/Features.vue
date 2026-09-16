<script setup>
import { computed, reactive } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import {
    UsersIcon,
    QrCodeIcon,
    MegaphoneIcon,
    FolderIcon,
    CalendarDaysIcon,
    MusicalNoteIcon,
    BanknotesIcon,
} from '@heroicons/vue/24/outline';
import WizardWorkspaceLayout from '@/Layouts/WizardWorkspaceLayout.vue';
import WizardFooterNav from '@/Components/Onboarding/WizardFooterNav.vue';
import CompactModuleCard from '@/Components/Onboarding/CompactModuleCard.vue';
import SpecializedModuleAccordion from '@/Components/Onboarding/SpecializedModuleAccordion.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    modules: { type: Array, required: true },
    fromReview: { type: Boolean, default: false },
});

const modules = reactive(props.modules.map((m) => ({ ...m, settings: { ...m.settings } })));

const icons = {
    member_management: UsersIcon,
    attendance: QrCodeIcon,
    announcements: MegaphoneIcon,
    files: FolderIcon,
    events: CalendarDaysIcon,
    music_library: MusicalNoteIcon,
    finance: BanknotesIcon,
};

const core = computed(() => modules.filter((m) => m.category === 'core' || m.category === 'standard'));
const specialized = computed(() => modules.filter((m) => m.category === 'specialized'));

function toggle(mod) {
    mod.is_enabled = !mod.is_enabled;
}

function updateSetting(mod, key, value) {
    mod.settings[key] = value;
}

const anyEnabled = computed(() => modules.some((m) => m.is_enabled));

const form = useForm({
    modules: [],
    return_to: props.fromReview ? 'review' : null,
});

function submit() {
    form.modules = modules.map((m) => ({ key: m.key, is_enabled: m.is_enabled, settings: m.settings }));
    form.post(route('onboarding.features.store'));
}
</script>

<template>
    <Head title="Choose Feature Modules" />

    <WizardWorkspaceLayout step="features" content-class="max-w-5xl">
        <h1 class="font-heading text-2xl font-extrabold text-tertiary-900 tracking-tight">Choose Your Feature Modules</h1>
        <p class="mt-1.5 text-sm text-tertiary-500">Every organization gets the essentials. Add specialized modules for the kind of work you do &mdash; you can change this later.</p>

        <section class="mt-8">
            <h3 class="text-xs font-bold uppercase tracking-wide text-tertiary-400 mb-3">Core Modules</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <CompactModuleCard v-for="mod in core" :key="mod.key" :module="mod" :icon="icons[mod.key]"
                    @toggle="toggle(mod)" @update-setting="(k, v) => updateSetting(mod, k, v)" />
            </div>
        </section>

        <section v-if="specialized.length" class="mt-8">
            <h3 class="text-xs font-bold uppercase tracking-wide text-tertiary-400 mb-3">Specialized Modules</h3>
            <div class="space-y-3">
                <SpecializedModuleAccordion v-for="mod in specialized" :key="mod.key" :module="mod" :icon="icons[mod.key]"
                    @toggle="toggle(mod)" @update-setting="(k, v) => updateSetting(mod, k, v)" />
            </div>
        </section>

        <div v-if="!anyEnabled" class="mt-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            Enable at least one module to continue.
        </div>
        <InputError :message="form.errors.modules" class="mt-2" />

        <WizardFooterNav :back-route="fromReview ? route('onboarding.review.show') : route('onboarding.members.show')">
            <template #primary>
                <PrimaryButton :disabled="!anyEnabled || form.processing" @click="submit">
                    {{ fromReview ? 'Save & Return to Review' : 'Next: Review' }}
                </PrimaryButton>
            </template>
        </WizardFooterNav>
    </WizardWorkspaceLayout>
</template>
