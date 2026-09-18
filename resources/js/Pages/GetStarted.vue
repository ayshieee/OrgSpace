<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { BuildingOffice2Icon, UserGroupIcon } from '@heroicons/vue/24/outline';
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Welcome to OrgSpace" />

    <OnboardingLayout
        :title="`Welcome to OrgSpace, ${user.name.split(' ')[0]}.`"
        subtitle="Are you starting a new organization, or joining one that's already here?"
    >
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Link :href="route('onboarding.profile.show')"
                class="group flex flex-col items-start p-6 rounded-2xl border border-neutral-200 hover:border-primary-300 hover:bg-primary-50/40 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-primary-50 border border-primary-100 flex items-center justify-center text-primary-600 mb-4">
                    <BuildingOffice2Icon class="w-6 h-6" />
                </div>
                <h3 class="font-heading font-bold text-tertiary-900">Create an Organization</h3>
                <p class="text-sm text-tertiary-500 mt-1 mb-5">Set up your organization's roles, features, and members from scratch.</p>
                <span class="mt-auto inline-flex items-center justify-center w-full rounded-lg border border-transparent bg-gradient-to-b from-secondary-400 to-secondary-500 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-tertiary-900 shadow-soft transition-all duration-200 ease-ios group-hover:shadow-elevated group-hover:from-secondary-300 group-hover:to-secondary-400">
                    Create Organization
                </span>
            </Link>

            <Link :href="route('join.index')"
                class="group flex flex-col items-start p-6 rounded-2xl border border-neutral-200 hover:border-primary-300 hover:bg-primary-50/40 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-secondary-50 border border-secondary-100 flex items-center justify-center text-secondary-600 mb-4">
                    <UserGroupIcon class="w-6 h-6" />
                </div>
                <h3 class="font-heading font-bold text-tertiary-900">Join an Organization</h3>
                <p class="text-sm text-tertiary-500 mt-1 mb-5">Find your organization and request to join as a member.</p>
                <span class="mt-auto inline-flex items-center justify-center w-full rounded-lg border border-secondary-400 bg-secondary-50 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-secondary-800 shadow-sm transition-all duration-200 ease-ios group-hover:bg-secondary-100 group-hover:border-secondary-500 group-hover:shadow-soft">
                    Find My Organization
                </span>
            </Link>
        </div>

        <p class="mt-6 text-center text-xs text-tertiary-400">
            Not sure yet? You can join additional organizations anytime from your dashboard.
        </p>
    </OnboardingLayout>
</template>
