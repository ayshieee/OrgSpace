<script setup>
import { TrashIcon, CheckCircleIcon, ExclamationCircleIcon, UserPlusIcon } from '@heroicons/vue/24/outline';

defineProps({
    rows: { type: Array, required: true },
    roles: { type: Array, required: true },
});

const emit = defineEmits(['update-row', 'remove-row']);

function update(index, field, value) {
    emit('update-row', index, field, value);
}
</script>

<template>
    <div class="overflow-x-auto border border-neutral-200 rounded-xl">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-neutral-50 border-b border-neutral-200 text-left text-tertiary-600">
                    <th class="px-3 py-2.5 font-semibold">Status</th>
                    <th class="px-3 py-2.5 font-semibold">First Name</th>
                    <th class="px-3 py-2.5 font-semibold">M.I.</th>
                    <th class="px-3 py-2.5 font-semibold">Surname</th>
                    <th class="px-3 py-2.5 font-semibold">Student ID</th>
                    <th class="px-3 py-2.5 font-semibold">Email</th>
                    <th class="px-3 py-2.5 font-semibold">Role</th>
                    <th class="px-3 py-2.5"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, index) in rows" :key="index" class="border-b border-neutral-100 last:border-0" :class="row.status === 'error' ? 'bg-red-50/60' : ''">
                    <td class="px-3 py-2 align-top">
                        <span v-if="row.status === 'new_user'" class="inline-flex items-center gap-1 text-emerald-700 text-xs font-semibold">
                            <UserPlusIcon class="w-4 h-4" /> New
                        </span>
                        <span v-else-if="row.status === 'existing_user'" class="inline-flex items-center gap-1 text-primary-700 text-xs font-semibold">
                            <CheckCircleIcon class="w-4 h-4" /> Existing
                        </span>
                        <span v-else-if="row.status === 'error'" class="inline-flex items-center gap-1 text-red-600 text-xs font-semibold">
                            <ExclamationCircleIcon class="w-4 h-4" /> Error
                        </span>
                        <span v-else class="text-tertiary-300 text-xs">&mdash;</span>
                    </td>
                    <td class="px-3 py-2 align-top">
                        <input :value="row.first_name" @input="update(index, 'first_name', $event.target.value)"
                            class="w-24 rounded border-tertiary-200 text-sm focus:border-primary-500 focus:ring-primary-500" />
                    </td>
                    <td class="px-3 py-2 align-top">
                        <input :value="row.m_i" @input="update(index, 'm_i', $event.target.value)"
                            class="w-12 rounded border-tertiary-200 text-sm focus:border-primary-500 focus:ring-primary-500" />
                    </td>
                    <td class="px-3 py-2 align-top">
                        <input :value="row.surname" @input="update(index, 'surname', $event.target.value)"
                            class="w-28 rounded border-tertiary-200 text-sm focus:border-primary-500 focus:ring-primary-500" />
                    </td>
                    <td class="px-3 py-2 align-top">
                        <input :value="row.student_id" @input="update(index, 'student_id', $event.target.value)"
                            class="w-28 rounded border-tertiary-200 text-sm focus:border-primary-500 focus:ring-primary-500" />
                    </td>
                    <td class="px-3 py-2 align-top">
                        <input :value="row.email" @input="update(index, 'email', $event.target.value)"
                            class="w-48 rounded text-sm focus:ring-primary-500"
                            :class="row.errors?.email ? 'border-red-400' : 'border-tertiary-200 focus:border-primary-500'" />
                        <p v-if="row.errors && Object.keys(row.errors).length" class="mt-1 text-[11px] text-red-600">
                            {{ Object.values(row.errors)[0] }}
                        </p>
                    </td>
                    <td class="px-3 py-2 align-top">
                        <select :value="row.role_id" @change="update(index, 'role_id', $event.target.value || null)"
                            class="w-32 rounded border-tertiary-200 text-sm focus:border-primary-500 focus:ring-primary-500">
                            <option :value="null">No role</option>
                            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                        </select>
                    </td>
                    <td class="px-3 py-2 align-top">
                        <button type="button" @click="emit('remove-row', index)" class="text-tertiary-300 hover:text-red-500">
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
