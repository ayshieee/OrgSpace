<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    UsersIcon,
    ShieldCheckIcon,
    CalendarDaysIcon,
    ChartBarIcon,
    CheckCircleIcon,
    InboxIcon,
    QrCodeIcon,
    MegaphoneIcon,
    FolderIcon,
    MusicalNoteIcon,
    BanknotesIcon,
    DocumentChartBarIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon as CheckIconSolid, XMarkIcon as XMarkIconSolid } from '@heroicons/vue/24/solid';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    canReview: { type: Boolean, default: false },
    canViewReports: { type: Boolean, default: false },
    eligibility: { type: Object, default: null },
    stats: { type: Object, required: true },
    pendingApprovals: { type: Array, default: () => [] },
    activity: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
    modules: { type: Array, default: () => [] },
});

const moduleIcons = {
    member_management: UsersIcon,
    attendance: QrCodeIcon,
    announcements: MegaphoneIcon,
    files: FolderIcon,
    events: CalendarDaysIcon,
    music_library: MusicalNoteIcon,
    finance: BanknotesIcon,
};

const barColors = ['bg-primary-500', 'bg-secondary-500', 'bg-tertiary-400', 'bg-emerald-500', 'bg-sky-500', 'bg-amber-500'];

const processingId = ref(null);

function respond(requestId, action) {
    processingId.value = requestId;
    router.post(route(`organizations.join-requests.${action}`, [props.organization.id, requestId]), {}, {
        preserveScroll: true,
        onFinish: () => (processingId.value = null),
    });
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>Dashboard</template>

        <!-- Stat cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard
                :icon="UsersIcon"
                label="Active Members"
                :value="stats.total_members"
                :caption="stats.new_members_30d > 0 ? `+${stats.new_members_30d} new in the last 30 days` : 'No new members in the last 30 days'"
            />
            <StatCard
                :icon="ShieldCheckIcon"
                tone="secondary"
                label="Officers & Leads"
                :value="stats.elevated_members"
                caption="Members holding a leadership role"
            />
            <StatCard
                :icon="CalendarDaysIcon"
                tone="neutral"
                label="Upcoming Events"
                :value="stats.upcoming_events"
                :caption="stats.upcoming_events > 0 ? 'Scheduled ahead' : 'Nothing scheduled yet'"
            />
            <StatCard
                :icon="ChartBarIcon"
                tone="primary"
                label="Attendance Index"
                :value="stats.attendance_index !== null ? `${stats.attendance_index}%` : '—'"
                :caption="stats.attendance_index !== null ? 'Average across all sessions' : 'No attendance sessions yet'"
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Pending Approvals -->
                <div v-if="canReview" id="pending-approvals" class="bg-white border border-neutral-200 rounded-xl scroll-mt-6">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
                        <h2 class="font-heading font-bold text-tertiary-900">Pending Approvals</h2>
                        <span v-if="pendingApprovals.length" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-secondary-100 text-secondary-700">
                            {{ pendingApprovals.length }} Pending
                        </span>
                    </div>

                    <div v-if="pendingApprovals.length" class="divide-y divide-neutral-100">
                        <div v-for="req in pendingApprovals" :key="req.id" class="flex items-center justify-between gap-4 px-5 py-4">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-tertiary-800 truncate">{{ req.name }}</p>
                                <p class="text-xs text-tertiary-400 truncate">{{ req.email }}</p>
                                <p class="text-[11px] text-tertiary-300 mt-0.5">Requested {{ req.requested_at }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" :disabled="processingId === req.id" @click="respond(req.id, 'approve')"
                                    class="inline-flex items-center gap-1 rounded-md bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-700 disabled:opacity-50">
                                    <CheckIconSolid class="w-3.5 h-3.5" /> Approve
                                </button>
                                <button type="button" :disabled="processingId === req.id" @click="respond(req.id, 'deny')"
                                    class="inline-flex items-center gap-1 rounded-md border border-neutral-200 px-3 py-1.5 text-xs font-semibold text-tertiary-500 hover:bg-neutral-50 disabled:opacity-50">
                                    <XMarkIconSolid class="w-3.5 h-3.5" /> Deny
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-10 text-center px-5">
                        <CheckCircleIcon class="w-8 h-8 text-emerald-400 mb-2" />
                        <p class="text-sm font-semibold text-tertiary-700">All caught up!</p>
                        <p class="text-xs text-tertiary-400 mt-0.5">No pending requests need your attention.</p>
                    </div>
                </div>

                <!-- Activity Feed -->
                <div class="bg-white border border-neutral-200 rounded-xl">
                    <div class="px-5 py-4 border-b border-neutral-100">
                        <h2 class="font-heading font-bold text-tertiary-900">Org-Wide Activity</h2>
                    </div>

                    <div v-if="activity.length" class="divide-y divide-neutral-100">
                        <div v-for="item in activity" :key="item.key" class="px-5 py-3.5 text-sm">
                            <p class="text-tertiary-700"><span class="font-semibold text-tertiary-900">{{ item.actor }}</span> {{ item.message }}</p>
                            <p class="text-xs text-tertiary-400 mt-0.5">{{ item.timestamp }}</p>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-10 text-center px-5">
                        <InboxIcon class="w-8 h-8 text-tertiary-300 mb-2" />
                        <p class="text-sm font-semibold text-tertiary-700">No Recent Activity</p>
                        <p class="text-xs text-tertiary-400 mt-0.5 max-w-xs">Member joins and join-request decisions will show up here.</p>
                    </div>
                </div>
            </div>

            <!-- Right column -->
            <div class="space-y-6">
                <!-- Role Governance -->
                <div class="bg-white border border-neutral-200 rounded-xl p-5">
                    <h2 class="font-heading font-bold text-tertiary-900 mb-4">Role Governance</h2>

                    <div v-if="roles.length && stats.total_members > 0">
                        <div class="flex w-full h-2.5 rounded-full overflow-hidden bg-neutral-100 mb-4">
                            <div v-for="(role, index) in roles" :key="role.id" :class="barColors[index % barColors.length]" :style="{ width: `${role.percent}%` }"></div>
                        </div>
                        <div class="space-y-2.5">
                            <div v-for="(role, index) in roles" :key="role.id" class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 text-tertiary-700 font-medium truncate">
                                    <span class="w-2 h-2 rounded-full shrink-0" :class="barColors[index % barColors.length]"></span>
                                    {{ role.name }}
                                </span>
                                <span class="text-xs text-tertiary-400 shrink-0">{{ role.count }} &middot; {{ role.percent }}%</span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-tertiary-400">No roles configured yet.</p>
                </div>

                <!-- Attendance Reports (view_reports only) -->
                <div v-if="canViewReports" class="bg-gradient-to-br from-primary-600 to-primary-800 rounded-xl p-5">
                    <h2 class="flex items-center gap-1.5 font-heading font-bold text-white mb-1">
                        <DocumentChartBarIcon class="w-4 h-4 text-primary-200" /> Attendance Reports
                    </h2>
                    <p class="text-xs text-primary-100 mb-4">Member standing based on attendance history.</p>

                    <div v-if="eligibility && eligibility.total_with_data > 0">
                        <div class="flex items-baseline gap-1.5 mb-1">
                            <span class="text-2xl font-heading font-extrabold text-white">{{ eligibility.eligible }}/{{ eligibility.total_with_data }}</span>
                            <span class="text-sm font-semibold text-primary-100">{{ eligibility.percent }}%</span>
                        </div>
                        <p class="text-xs text-primary-100 mb-4">Members at or above {{ eligibility.threshold }}% attendance</p>
                        <div class="h-1.5 rounded-full bg-white/20 overflow-hidden mb-4">
                            <div class="h-full bg-secondary-400" :style="{ width: `${eligibility.percent}%` }"></div>
                        </div>
                    </div>
                    <p v-else class="text-xs text-primary-100 mb-4">No attendance sessions recorded yet.</p>

                    <div class="flex items-center gap-2">
                        <Link :href="route('organizations.attendance.index', organization.id)"
                            class="flex-1 text-center rounded-md bg-secondary px-3 py-2 text-xs font-semibold uppercase tracking-wide text-tertiary-900 hover:bg-secondary-400">
                            Open Attendance
                        </Link>
                        <a :href="route('organizations.reports.attendance', organization.id)"
                            class="flex items-center gap-1 text-xs font-semibold text-primary-100 hover:text-white">
                            <ArrowDownTrayIcon class="w-3.5 h-3.5" /> CSV
                        </a>
                    </div>
                </div>

                <!-- Feature Modules -->
                <div class="bg-white border border-neutral-200 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-heading font-bold text-tertiary-900">Feature Modules</h2>
                        <span class="text-xs font-semibold text-tertiary-400">{{ stats.enabled_modules }}/{{ stats.total_modules }}</span>
                    </div>
                    <div class="space-y-2.5">
                        <div v-for="mod in modules" :key="mod.key" class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2 text-sm text-tertiary-700 min-w-0">
                                <component :is="moduleIcons[mod.key]" class="w-4 h-4 text-tertiary-400 shrink-0" />
                                <span class="truncate">{{ mod.name }}</span>
                            </span>
                            <span class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded shrink-0"
                                :class="mod.is_enabled ? 'bg-primary-50 text-primary-600' : 'bg-neutral-100 text-tertiary-300'">
                                {{ mod.is_enabled ? 'On' : 'Off' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
