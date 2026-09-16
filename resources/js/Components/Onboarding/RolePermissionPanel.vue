<script setup>
import { computed } from 'vue';
import {
    UsersIcon,
    Cog6ToothIcon,
    CalendarDaysIcon,
    MegaphoneIcon,
    FolderIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';

const props = defineProps({
    role: { type: Object, required: true },
    permissionKeys: { type: Object, required: true },
});

const emit = defineEmits(['rename', 'toggle']);

const descriptions = {
    view_roster: 'Can see the list of all active members.',
    manage_roster: 'Can add, remove, or edit member profiles.',
    manage_org_settings: 'Can update the organization profile and details.',
    manage_roles: 'Can create and edit roles & permissions.',
    manage_modules: 'Can enable or disable feature modules.',
    manage_events: 'Can create and manage events and the calendar.',
    manage_attendance: 'Can record and manage meeting attendance.',
    manage_announcements: 'Can broadcast messages to the entire organization.',
    manage_files: 'Can upload and manage shared files.',
    manage_library: 'Can manage the music library and sheet music.',
    manage_finance: 'Can manage budgets and member dues.',
    view_reports: 'Can view organization reports and analytics.',
};

const groups = [
    { label: 'Membership Management', icon: UsersIcon, keys: ['view_roster', 'manage_roster'] },
    { label: 'Organization Settings', icon: Cog6ToothIcon, keys: ['manage_org_settings', 'manage_roles', 'manage_modules'] },
    { label: 'Events & Attendance', icon: CalendarDaysIcon, keys: ['manage_events', 'manage_attendance'] },
    { label: 'Announcements', icon: MegaphoneIcon, keys: ['manage_announcements'] },
    { label: 'Files & Resources', icon: FolderIcon, keys: ['manage_files', 'manage_library'] },
    { label: 'Finance & Reports', icon: ChartBarIcon, keys: ['manage_finance', 'view_reports'] },
];

const visibleGroups = computed(() =>
    groups
        .map((group) => ({ ...group, keys: group.keys.filter((key) => key in props.permissionKeys) }))
        .filter((group) => group.keys.length > 0)
);

function isChecked(key) {
    return props.role.permissions.includes(key);
}
</script>

<template>
    <div class="border border-neutral-200 rounded-xl bg-white p-5">
        <div class="flex items-start justify-between gap-3 mb-1">
            <div class="flex items-baseline gap-1.5 min-w-0 flex-1">
                <input
                    :value="role.name"
                    @input="emit('rename', $event.target.value)"
                    class="flex-1 min-w-0 font-heading font-bold text-tertiary-900 bg-transparent border-0 border-b border-transparent focus:border-primary-400 focus:ring-0 px-0 py-0"
                />
                <span class="text-tertiary-400 font-medium shrink-0">Permissions</span>
            </div>
            <span v-if="!role.isCustom" class="shrink-0 px-2.5 py-1 rounded-full text-[11px] font-bold bg-neutral-100 text-tertiary-600">
                Template Role
            </span>
        </div>
        <p class="text-xs text-tertiary-400 mb-5">Editing base permissions for this role.</p>

        <div v-for="group in visibleGroups" :key="group.label" class="mb-5 last:mb-0">
            <h4 class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-tertiary-400 mb-2">
                <component :is="group.icon" class="w-3.5 h-3.5" /> {{ group.label }}
            </h4>
            <div class="rounded-lg bg-neutral-50 border border-neutral-100 divide-y divide-neutral-100">
                <div v-for="key in group.keys" :key="key" class="flex items-center justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-tertiary-800">{{ permissionKeys[key] }}</p>
                        <p v-if="descriptions[key]" class="text-xs text-tertiary-400">{{ descriptions[key] }}</p>
                    </div>
                    <ToggleSwitch :model-value="isChecked(key)" @update:model-value="emit('toggle', key)" />
                </div>
            </div>
        </div>
    </div>
</template>
