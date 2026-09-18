<script setup>
import AccountLayout from '@/Layouts/AccountLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import ProfileHeader from '@/Components/Profile/ProfileHeader.vue';
import PersonalDetails from '@/Components/Profile/PersonalDetails.vue';
import WorkOrganizations from '@/Components/Profile/WorkOrganizations.vue';
import EducationSection from '@/Components/Profile/EducationSection.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    memberships: {
        type: Array,
        required: true,
    },
    educations: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Profile" />

    <AccountLayout>
        <div class="max-w-5xl mx-auto space-y-6">
            <ProfileHeader :user="user" :memberships="memberships" :must-verify-email="mustVerifyEmail" :status="status" />

            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl border border-neutral-200 p-6">
                        <WorkOrganizations :memberships="memberships" />
                    </div>

                    <div class="bg-white rounded-xl border border-neutral-200 p-6">
                        <EducationSection :educations="educations" />
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-xl border border-neutral-200 p-6">
                        <PersonalDetails :user="user" />
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div id="password" class="bg-white rounded-xl border border-neutral-200 p-6 scroll-mt-6">
                    <UpdatePasswordForm />
                </div>

                <div id="delete-account" class="bg-white rounded-xl border border-neutral-200 p-6 scroll-mt-6">
                    <DeleteUserForm />
                </div>
            </div>
        </div>
    </AccountLayout>
</template>
