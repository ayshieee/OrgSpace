<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import LogoUpload from '@/Components/LogoUpload.vue';

const props = defineProps({
    organization: { type: Object, default: null },
    types: { type: Object, required: true },
    fromReview: { type: Boolean, default: false },
});

const form = useForm({
    name: props.organization?.name ?? '',
    type: props.organization?.type ?? '',
    description: props.organization?.description ?? '',
    logo: null,
    return_to: props.fromReview ? 'review' : null,
});

const submit = () => {
    form.post(route('onboarding.profile.store'), { forceFormData: true });
};
</script>

<template>
    <Head title="Organization Profile" />

    <OnboardingLayout
        step="profile"
        title="Organization Profile"
        subtitle="Let's start by getting the basics down. This information will be visible to students exploring OrgSpace."
    >
        <form @submit.prevent="submit" class="space-y-6">
            <div class="flex justify-center">
                <LogoUpload v-model="form.logo" :existing-url="organization?.logo_path ? `/storage/${organization.logo_path}` : null" />
            </div>
            <InputError :message="form.errors.logo" class="text-center" />

            <div>
                <InputLabel for="name" value="Organization Name" />
                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus placeholder="e.g. The Debate Society" />
                <InputError :message="form.errors.name" class="mt-1" />
            </div>

            <div>
                <InputLabel for="type" value="Organization Type" />
                <select id="type" v-model="form.type" required
                    class="mt-1 block w-full rounded-md border-tertiary-200 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="" disabled>Select a category&hellip;</option>
                    <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                </select>
                <InputError :message="form.errors.type" class="mt-1" />
                <p class="mt-1 text-xs text-tertiary-400">We'll use this to recommend feature modules later.</p>
            </div>

            <div>
                <InputLabel for="description" value="Description (optional)" />
                <textarea id="description" v-model="form.description" rows="3"
                    class="mt-1 block w-full rounded-md border-tertiary-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    placeholder="Briefly describe your organization's mission and activities..."></textarea>
                <InputError :message="form.errors.description" class="mt-1" />
                <p class="mt-1 text-xs text-tertiary-400">Keep it concise. You can add more details later on your public page.</p>
            </div>

            <div class="pt-6 mt-2 border-t border-neutral-200 flex items-center justify-between">
                <Link v-if="fromReview" :href="route('onboarding.review.show')" class="inline-flex items-center gap-1.5 text-sm font-semibold text-tertiary-500 hover:text-tertiary-700">
                    <ArrowLeftIcon class="w-4 h-4" /> Back
                </Link>
                <span v-else></span>
                <PrimaryButton :disabled="form.processing">
                    {{ fromReview ? 'Save & Return to Review' : 'Next' }}
                </PrimaryButton>
            </div>
        </form>
    </OnboardingLayout>

    <p class="text-center text-xs text-tertiary-400 mt-6">&copy; 2024 OrgSpace</p>
</template>
