<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, TrashIcon, UsersIcon, ShieldCheckIcon, ClipboardDocumentCheckIcon, ChartBarIcon, ArrowDownTrayIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { CheckIcon as CheckIconSolid, XMarkIcon as XMarkIconSolid } from '@heroicons/vue/24/solid';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import MemberDetailDrawer from '@/Components/Organizations/MemberDetailDrawer.vue';
import AddMemberModal from '@/Components/Organizations/AddMemberModal.vue';
import { getInitials, colorForId } from '@/utils/initials';

const props = defineProps({
    organization: { type: Object, required: true },
    members: { type: Array, required: true },
    roles: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
    currentMemberId: { type: String, required: true },
    stats: { type: Object, required: true },
    pendingApprovals: { type: Array, default: () => [] },
});

const search = ref('');
const processingId = ref(null);
const selected = ref([]);
const bulkRoleId = ref('');
const viewing = ref(null);
const showAddMember = ref(false);

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.members;
    return props.members.filter((m) => m.name.toLowerCase().includes(q) || m.email.toLowerCase().includes(q));
});

const selectableIds = computed(() => filtered.value.filter((m) => m.id !== props.currentMemberId).map((m) => m.id));
const allSelected = computed(() => selectableIds.value.length > 0 && selectableIds.value.every((id) => selected.value.includes(id)));

function toggleSelectAll() {
    selected.value = allSelected.value ? [] : [...selectableIds.value];
}

function toggleSelect(id) {
    const i = selected.value.indexOf(id);
    if (i === -1) selected.value.push(id); else selected.value.splice(i, 1);
}

function attendanceTone(rate) {
    if (rate === null) return 'bg-neutral-100 text-tertiary-400';
    if (rate >= 90) return 'bg-emerald-100 text-emerald-700';
    if (rate >= 75) return 'bg-secondary-100 text-secondary-700';
    return 'bg-red-100 text-red-700';
}

function changeRole(member, roleId) {
    router.patch(route('organizations.members.update-role', [props.organization.id, member.id]), { role_id: roleId || null }, {
        preserveScroll: true,
    });
}

function removeMember(member) {
    if (!confirm(`Remove ${member.name} from ${props.organization.name}?`)) return;
    router.delete(route('organizations.members.destroy', [props.organization.id, member.id]), { preserveScroll: true });
}

function bulkRemove() {
    if (!selected.value.length) return;
    if (!confirm(`Remove ${selected.value.length} selected member${selected.value.length === 1 ? '' : 's'} from ${props.organization.name}?`)) return;
    router.post(route('organizations.members.bulk-destroy', props.organization.id), { member_ids: selected.value }, {
        preserveScroll: true,
        onSuccess: () => (selected.value = []),
    });
}

function bulkChangeRole() {
    if (!selected.value.length || !bulkRoleId.value) return;
    const roleId = bulkRoleId.value === 'none' ? null : bulkRoleId.value;
    router.post(route('organizations.members.bulk-update-role', props.organization.id), { member_ids: selected.value, role_id: roleId }, {
        preserveScroll: true,
        onSuccess: () => {
            selected.value = [];
            bulkRoleId.value = '';
        },
    });
}

function exportSelected() {
    const ids = selected.value.length ? `?ids=${selected.value.join(',')}` : '';
    window.location.href = route('organizations.members.export', props.organization.id) + ids;
}

function respond(requestId, action) {
    processingId.value = requestId;
    router.post(route(`organizations.join-requests.${action}`, [props.organization.id, requestId]), {}, {
        preserveScroll: true,
        onFinish: () => (processingId.value = null),
    });
}
</script>

<template>
    <Head title="Members" />

    <AuthenticatedLayout>
        <template #header>Members</template>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard :icon="UsersIcon" label="Active Members" :value="stats.total_members" caption="Currently active in this organization" />
            <StatCard :icon="ShieldCheckIcon" tone="secondary" label="Officers & Leads" :value="stats.elevated_members" caption="Holding a leadership role" />
            <StatCard v-if="canManage" :icon="ClipboardDocumentCheckIcon" tone="neutral" label="Pending Sign-off" :value="stats.pending_join_requests" :caption="stats.pending_join_requests > 0 ? 'Awaiting your review' : 'All caught up'" />
            <StatCard :icon="ChartBarIcon" tone="primary" label="Attendance Index" :value="stats.org_attendance_rate !== null ? `${stats.org_attendance_rate}%` : '—'" :caption="stats.org_attendance_rate !== null ? 'Average across all sessions' : 'No attendance sessions yet'" />
        </div>

        <div v-if="canManage && pendingApprovals.length" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 mb-6">
            <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
                <h2 class="font-heading font-bold text-tertiary-900">Adviser Sign-off Queue</h2>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-secondary-100 text-secondary-700">{{ pendingApprovals.length }} Pending</span>
            </div>
            <div class="divide-y divide-neutral-100">
                <div v-for="req in pendingApprovals" :key="req.id" class="flex items-center justify-between gap-4 px-5 py-3.5">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-tertiary-800 truncate">{{ req.name }}</p>
                        <p class="text-xs text-tertiary-400 truncate">{{ req.email }} &middot; Requested {{ req.requested_at }}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" :disabled="processingId === req.id" @click="respond(req.id, 'approve')"
                            class="inline-flex items-center gap-1 rounded-md bg-gradient-to-b from-secondary-400 to-secondary-500 px-3 py-1.5 text-xs font-semibold text-tertiary-900 shadow-sm transition-all duration-200 ease-ios hover:shadow-soft hover:from-secondary-300 hover:to-secondary-400 disabled:opacity-50">
                            <CheckIconSolid class="w-3.5 h-3.5" /> Approve
                        </button>
                        <button type="button" :disabled="processingId === req.id" @click="respond(req.id, 'deny')"
                            class="inline-flex items-center gap-1 rounded-md border border-neutral-200 px-3 py-1.5 text-xs font-semibold text-tertiary-500 hover:bg-neutral-50 disabled:opacity-50">
                            <XMarkIconSolid class="w-3.5 h-3.5" /> Deny
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between mb-4">
            <h1 class="font-heading text-xl font-extrabold text-tertiary-900">Members Directory <span class="text-tertiary-400 font-medium">({{ members.length }})</span></h1>
            <div class="flex items-center gap-3">
                <div v-if="canManage && selected.length" class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-tertiary-600">{{ selected.length }} selected</span>
                    <select v-model="bulkRoleId" @change="bulkChangeRole"
                        class="text-xs rounded-md border-neutral-200 focus:border-primary-500 focus:ring-primary-500 py-1.5">
                        <option value="" disabled>Change role…</option>
                        <option value="none">No role</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                    </select>
                    <button type="button" @click="exportSelected" class="inline-flex items-center gap-1 rounded-md border border-neutral-200 px-3 py-1.5 text-xs font-semibold text-tertiary-600 hover:bg-neutral-50">
                        <ArrowDownTrayIcon class="w-3.5 h-3.5" /> Export Selected
                    </button>
                    <button type="button" @click="bulkRemove" class="inline-flex items-center gap-1 rounded-md border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">
                        <TrashIcon class="w-3.5 h-3.5" /> Remove Selected
                    </button>
                </div>
                <button v-else-if="canManage" type="button" @click="exportSelected"
                    class="inline-flex items-center gap-1 rounded-md border border-neutral-200 px-3 py-1.5 text-xs font-semibold text-tertiary-600 hover:bg-neutral-50">
                    <ArrowDownTrayIcon class="w-3.5 h-3.5" /> Export All
                </button>
                <button v-if="canManage" type="button" @click="showAddMember = true"
                    class="inline-flex items-center gap-1 rounded-md bg-gradient-to-b from-secondary-400 to-secondary-500 px-3 py-1.5 text-xs font-semibold text-tertiary-900 shadow-soft transition-all duration-200 ease-ios hover:shadow-elevated hover:from-secondary-300 hover:to-secondary-400 active:scale-[0.98]">
                    <PlusIcon class="w-3.5 h-3.5" /> Add Member
                </button>
                <div class="relative">
                    <MagnifyingGlassIcon class="w-4 h-4 text-tertiary-300 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input v-model="search" type="text" placeholder="Search members..."
                        class="w-56 pl-9 pr-3 py-2 text-sm rounded-md border-neutral-200 focus:border-primary-500 focus:ring-primary-500" />
                </div>
            </div>
        </div>

        <div class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 text-left text-[11px] font-bold uppercase tracking-wide text-tertiary-400">
                        <th v-if="canManage" class="pl-5 py-3 w-8">
                            <input type="checkbox" :checked="allSelected" @change="toggleSelectAll" class="rounded border-tertiary-300 text-primary-600 focus:ring-primary-500" />
                        </th>
                        <th class="px-3 py-3">Member</th>
                        <th class="px-3 py-3">Student ID</th>
                        <th class="px-3 py-3">Role</th>
                        <th class="px-3 py-3">Attendance</th>
                        <th class="px-3 py-3">Joined</th>
                        <th v-if="canManage" class="px-3 py-3 w-8"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <tr v-for="member in filtered" :key="member.id" class="cursor-pointer hover:bg-neutral-50" @click="viewing = member">
                        <td v-if="canManage" class="pl-5 py-3" @click.stop>
                            <input v-if="member.id !== currentMemberId" type="checkbox" :checked="selected.includes(member.id)" @change="toggleSelect(member.id)"
                                class="rounded border-tertiary-300 text-primary-600 focus:ring-primary-500" />
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 overflow-hidden" :class="member.avatar_path ? '' : colorForId(member.user_id)">
                                    <img v-if="member.avatar_path" :src="`/storage/${member.avatar_path}`" class="w-full h-full object-cover" :alt="member.name" />
                                    <template v-else>{{ getInitials(member.name) }}</template>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-tertiary-800 truncate">
                                        {{ member.name }}
                                        <span v-if="member.id === currentMemberId" class="text-xs font-medium text-tertiary-400">(You)</span>
                                    </p>
                                    <p class="text-xs text-tertiary-400 truncate">{{ member.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-3 text-tertiary-500 font-mono text-xs">{{ member.membership_number ?? '—' }}</td>
                        <td class="px-3 py-3" @click.stop>
                            <select v-if="canManage" :value="member.role_id ?? ''" @change="changeRole(member, $event.target.value)"
                                class="text-xs rounded-md border-neutral-200 focus:border-primary-500 focus:ring-primary-500 py-1.5">
                                <option value="">No role</option>
                                <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                            </select>
                            <span v-else class="px-2.5 py-1 rounded-full text-xs font-semibold bg-neutral-100 text-tertiary-600">{{ member.role_name ?? 'No role' }}</span>
                        </td>
                        <td class="px-3 py-3">
                            <span class="px-2 py-1 rounded text-xs font-semibold" :class="attendanceTone(member.attendance_rate)">
                                {{ member.attendance_rate !== null ? `${member.attendance_rate}%` : 'No data' }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-tertiary-400 text-xs">{{ member.joined_at }}</td>
                        <td v-if="canManage" class="px-3 py-3" @click.stop>
                            <button v-if="member.id !== currentMemberId" type="button" @click="removeMember(member)" class="text-tertiary-300 hover:text-red-500">
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!filtered.length">
                        <td :colspan="canManage ? 7 : 5" class="px-5 py-8 text-center text-sm text-tertiary-400">No members match your search.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <MemberDetailDrawer v-if="viewing" :organization-id="organization.id" :member="viewing" :roles="roles"
            :can-manage="canManage" :is-self="viewing.id === currentMemberId" @close="viewing = null" />

        <AddMemberModal v-if="canManage" :show="showAddMember" :organization-id="organization.id" :roles="roles" @close="showAddMember = false" />
    </AuthenticatedLayout>
</template>
