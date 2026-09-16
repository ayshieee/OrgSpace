<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import WizardWorkspaceLayout from '@/Layouts/WizardWorkspaceLayout.vue';
import WizardFooterNav from '@/Components/Onboarding/WizardFooterNav.vue';
import RoleSidebar from '@/Components/Onboarding/RoleSidebar.vue';
import RolePermissionPanel from '@/Components/Onboarding/RolePermissionPanel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    roles: { type: Array, required: true },
    templates: { type: Object, required: true },
    permissionKeys: { type: Object, required: true },
    fromReview: { type: Boolean, default: false },
});

function seedFromTemplates() {
    return Object.entries(props.templates).map(([key, tpl]) => ({
        id: null,
        name: tpl.name,
        permissions: [...tpl.permissions],
        isCustom: false,
    }));
}

const roles = reactive(
    props.roles.length > 0
        ? props.roles.map((r) => ({ id: r.id, name: r.name, permissions: [...r.permissions], isCustom: true }))
        : seedFromTemplates()
);

const activeIndex = ref(0);
const activeRole = computed(() => roles[activeIndex.value]);

const canManageRoster = computed(() => roles.some((r) => r.permissions.includes('manage_roster')));

function rename(name) {
    roles[activeIndex.value].name = name;
}

function toggle(key) {
    const perms = roles[activeIndex.value].permissions;
    const i = perms.indexOf(key);
    if (i === -1) {
        perms.push(key);
    } else {
        perms.splice(i, 1);
    }
}

function remove(index) {
    roles.splice(index, 1);
    if (activeIndex.value >= roles.length) {
        activeIndex.value = Math.max(0, roles.length - 1);
    }
}

function addCustomRole() {
    roles.push({ id: null, name: 'New Role', permissions: [], isCustom: true });
    activeIndex.value = roles.length - 1;
}

const form = useForm({
    roles: [],
    return_to: props.fromReview ? 'review' : null,
});

const submit = () => {
    form.roles = roles.map((r) => ({ id: r.id, name: r.name, permissions: r.permissions }));
    form.post(route('onboarding.roles.store'));
};
</script>

<template>
    <Head title="Roles & Permissions" />

    <WizardWorkspaceLayout step="roles" content-class="max-w-5xl">
        <h1 class="font-heading text-2xl font-extrabold text-tertiary-900 tracking-tight">Step 2: Roles & Permissions</h1>
        <p class="mt-1.5 text-sm text-tertiary-500">Configure access levels for your organization members.</p>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-[280px_1fr] gap-6 items-start">
            <RoleSidebar :roles="roles" :active-index="activeIndex" @select="(i) => (activeIndex = i)" @remove="remove" @add="addCustomRole" />

            <RolePermissionPanel v-if="activeRole" :role="activeRole" :permission-keys="permissionKeys" @rename="rename" @toggle="toggle" />
        </div>

        <div v-if="!canManageRoster" class="mt-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            At least one role must be able to <strong>Manage Roster</strong> before you continue.
        </div>
        <InputError :message="form.errors.roles" class="mt-2" />

        <WizardFooterNav :back-route="fromReview ? route('onboarding.review.show') : route('onboarding.profile.show')">
            <template #primary>
                <PrimaryButton :disabled="form.processing || !canManageRoster" @click="submit">
                    {{ fromReview ? 'Save & Return to Review' : 'Next' }}
                </PrimaryButton>
            </template>
        </WizardFooterNav>
    </WizardWorkspaceLayout>
</template>
