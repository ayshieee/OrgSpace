<script setup>
import { ref, reactive } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    ClipboardDocumentIcon,
    CheckIcon,
    Cog6ToothIcon,
    ShieldCheckIcon,
    Squares2X2Icon,
    ExclamationTriangleIcon,
    PlusIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import LogoUpload from '@/Components/LogoUpload.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    roles: { type: Array, required: true },
    permissionKeys: { type: Object, required: true },
    modules: { type: Array, required: true },
});

const tabs = [
    { key: 'general', label: 'Organization Profile', icon: Cog6ToothIcon },
    { key: 'roles', label: 'Roles & Permissions', icon: ShieldCheckIcon },
    { key: 'modules', label: 'Feature Modules', icon: Squares2X2Icon },
    { key: 'danger', label: 'Danger Zone', icon: ExclamationTriangleIcon },
];
const activeTab = ref('general');

// --- General ---
const form = useForm({
    name: props.organization.name,
    logo: null,
    primary_color: props.organization.branding?.primary_color || '#2E418D',
    is_public: props.organization.is_public,
});
const submit = () => form.post(route('organization.settings.update'), { forceFormData: true });

function generateJoinCode() {
    router.post(route('organization.settings.join-code.generate'), {}, { preserveScroll: true });
}
const copied = ref(false);
function copyCode() {
    navigator.clipboard.writeText(props.organization.join_code);
    copied.value = true;
    setTimeout(() => (copied.value = false), 1500);
}

// --- Roles & Permissions (matrix: permissions as rows, roles as columns) ---
const roles = reactive(props.roles.map((r) => ({ ...r, permissions: [...r.permissions] })));
const rolesForm = useForm({ roles: [] });

function togglePermission(roleIndex, key) {
    const perms = roles[roleIndex].permissions;
    const i = perms.indexOf(key);
    if (i === -1) perms.push(key); else perms.splice(i, 1);
}

function addCustomRole() {
    roles.push({ id: null, name: 'New Role', permissions: [] });
}

function removeRole(index) {
    if (roles.length <= 1) return;
    roles.splice(index, 1);
}

function saveRoles() {
    rolesForm.roles = roles.map((r) => ({ id: r.id, name: r.name, permissions: r.permissions }));
    rolesForm.post(route('organization.settings.roles.update'), { preserveScroll: true });
}

// --- Feature Modules ---
const modules = reactive(props.modules.map((m) => ({ ...m })));
const modulesForm = useForm({ modules: [] });

function toggleModule(mod) {
    if (mod.locked) return;
    mod.is_enabled = !mod.is_enabled;
    modulesForm.modules = modules.map((m) => ({ key: m.key, is_enabled: m.is_enabled }));
    modulesForm.post(route('organization.settings.modules.update'), { preserveScroll: true });
}

// --- Danger Zone ---
function archive() {
    if (!confirm(`Archive ${props.organization.name}? Members will lose access until it's restored by support.`)) return;
    router.post(route('organization.settings.archive'));
}
</script>

<template>
    <Head title="Organization Settings" />

    <AuthenticatedLayout>
        <template #header>Settings</template>

        <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] gap-6">
            <nav class="space-y-1">
                <button v-for="tab in tabs" :key="tab.key" type="button" @click="activeTab = tab.key"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-left transition-colors"
                    :class="activeTab === tab.key ? 'bg-primary-50 text-primary-700' : 'text-tertiary-600 hover:bg-neutral-100 hover:text-tertiary-900'">
                    <component :is="tab.icon" class="w-4 h-4" /> {{ tab.label }}
                </button>
            </nav>

            <div>
                <!-- General -->
                <div v-if="activeTab === 'general'" class="space-y-6">
                    <form @submit.prevent="submit" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 p-6 space-y-6">
                        <h3 class="font-heading font-bold text-tertiary-900">Basic Information</h3>
                        <div>
                            <InputLabel for="name" value="Organization Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Logo" />
                            <div class="mt-1">
                                <LogoUpload v-model="form.logo" :existing-url="organization.logo_path ? `/storage/${organization.logo_path}` : null" />
                            </div>
                            <InputError :message="form.errors.logo" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="primary_color" value="Primary Color" />
                            <input id="primary_color" v-model="form.primary_color" type="color" class="mt-1 block h-10 w-20 rounded-md border-tertiary-200">
                        </div>
                        <div class="flex justify-end">
                            <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
                        </div>
                    </form>

                    <div class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 p-6">
                        <h3 class="font-heading font-bold text-tertiary-900 mb-4">Visibility & Join Code</h3>
                        <div class="flex items-center justify-between py-2">
                            <div>
                                <p class="text-sm font-semibold text-tertiary-800">Public organization</p>
                                <p class="text-xs text-tertiary-400 mt-0.5">Allow this organization to appear in the public browse/search list.</p>
                            </div>
                            <ToggleSwitch :model-value="form.is_public" @update:model-value="(v) => { form.is_public = v; submit(); }" />
                        </div>
                        <div class="py-3 border-t border-neutral-100 mt-3">
                            <p class="text-sm font-semibold text-tertiary-800 mb-2">Join Code</p>
                            <div v-if="organization.join_code" class="flex items-center gap-2">
                                <code class="px-3 py-2 rounded-md bg-neutral-100 font-mono text-sm tracking-widest text-tertiary-800">{{ organization.join_code }}</code>
                                <button type="button" @click="copyCode" class="text-tertiary-400 hover:text-tertiary-700">
                                    <CheckIcon v-if="copied" class="w-5 h-5 text-emerald-500" />
                                    <ClipboardDocumentIcon v-else class="w-5 h-5" />
                                </button>
                                <button type="button" @click="generateJoinCode" class="rounded-md border border-neutral-200 px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-tertiary-600 hover:bg-neutral-100">Regenerate</button>
                            </div>
                            <div v-else>
                                <p class="text-sm text-tertiary-400 mb-2">No join code yet.</p>
                                <button type="button" @click="generateJoinCode" class="rounded-md border border-neutral-200 px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-tertiary-600 hover:bg-neutral-100">Generate Join Code</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Roles & Permissions Matrix -->
                <div v-else-if="activeTab === 'roles'" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-heading font-bold text-tertiary-900">Roles & Permissions Matrix</h3>
                        <button type="button" @click="addCustomRole" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-600 hover:text-primary-700">
                            <PlusIcon class="w-4 h-4" /> Add Custom Role
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border-separate border-spacing-0">
                            <thead>
                                <tr>
                                    <th class="text-left font-semibold text-tertiary-400 pb-3 pr-4 sticky left-0 bg-white">Permission</th>
                                    <th v-for="(role, rIndex) in roles" :key="role.id ?? rIndex" class="pb-3 px-3 min-w-[150px]">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <input v-model="role.name" class="w-full text-center font-semibold text-tertiary-900 bg-transparent border-0 border-b border-transparent focus:border-primary-400 focus:ring-0 px-0" />
                                            <TrashIcon v-if="roles.length > 1" class="w-3.5 h-3.5 text-tertiary-300 hover:text-red-500 shrink-0 cursor-pointer" @click="removeRole(rIndex)" />
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(label, key) in permissionKeys" :key="key" class="border-t border-neutral-100"
                                    :class="key === 'manage_roster' ? 'bg-secondary-50/50' : ''">
                                    <td class="py-3 pr-4 text-tertiary-700 sticky left-0 bg-white whitespace-nowrap">{{ label }}</td>
                                    <td v-for="(role, rIndex) in roles" :key="(role.id ?? rIndex) + key" class="py-3 px-3 text-center">
                                        <button type="button" @click="togglePermission(rIndex, key)" class="inline-flex">
                                            <span class="w-5 h-5 rounded flex items-center justify-center border transition-colors"
                                                :class="role.permissions.includes(key) ? 'bg-primary border-primary' : 'border-tertiary-200 bg-white'">
                                                <CheckIcon v-if="role.permissions.includes(key)" class="w-3.5 h-3.5 text-white" />
                                            </span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end mt-5">
                        <PrimaryButton :disabled="rolesForm.processing" @click="saveRoles">Save Roles</PrimaryButton>
                    </div>
                    <InputError :message="rolesForm.errors.roles" class="mt-2" />
                </div>

                <!-- Feature Modules -->
                <div v-else-if="activeTab === 'modules'" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 p-5">
                    <h3 class="font-heading font-bold text-tertiary-900 mb-4">Feature Modules</h3>
                    <div class="divide-y divide-neutral-100">
                        <div v-for="mod in modules" :key="mod.key" class="flex items-center justify-between gap-4 py-3.5">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-tertiary-800">{{ mod.name }} <span v-if="mod.locked" class="text-[10px] uppercase font-bold text-tertiary-400 ml-1">Required</span></p>
                                <p class="text-xs text-tertiary-400">{{ mod.description }}</p>
                            </div>
                            <ToggleSwitch :model-value="mod.is_enabled" :disabled="mod.locked" @update:model-value="toggleModule(mod)" />
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div v-else-if="activeTab === 'danger'" class="bg-red-50 border border-red-200 rounded-xl p-6">
                    <h3 class="font-heading font-bold text-red-700 mb-2">Archive Organization</h3>
                    <p class="text-sm text-tertiary-500 mb-4">
                        Archiving hides {{ organization.name }} from every member's dashboard. This is reversible by support, but not by members — it is not a permanent delete.
                    </p>
                    <button type="button" @click="archive" class="rounded-md border border-red-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-red-600 hover:bg-red-100">
                        Archive Organization
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
