<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    PlusIcon,
    LockClosedIcon,
    QrCodeIcon,
    ChartBarIcon,
    RadioIcon,
    ClipboardDocumentCheckIcon,
    ShieldCheckIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline';
import QRCode from 'qrcode';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import NewSessionModal from '@/Components/Attendance/NewSessionModal.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    sessions: { type: Array, required: true },
    events: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
    canViewReports: { type: Boolean, default: false },
    myQrToken: { type: String, default: null },
    kpis: { type: Object, required: true },
    eligibility: { type: Object, default: null },
});

const myQrCanvas = ref(null);

function renderMyQr() {
    if (!myQrCanvas.value || !props.myQrToken) return;
    QRCode.toCanvas(myQrCanvas.value, props.myQrToken, {
        width: 152,
        margin: 1,
        color: { dark: '#111827', light: '#FFFFFF' },
    });
}

onMounted(renderMyQr);
watch(() => props.myQrToken, renderMyQr);

const showNewSessionModal = ref(false);

const tab = ref('live');
const filteredSessions = computed(() => props.sessions.filter((s) => (tab.value === 'live' ? !s.is_closed : s.is_closed)));
</script>

<template>
    <Head title="Attendance" />

    <AuthenticatedLayout>
        <template #header>Attendance</template>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <h1 class="font-heading text-xl font-extrabold text-tertiary-900">Attendance</h1>
            <div class="flex flex-wrap items-center gap-3">
                <div class="bg-neutral-100 p-1 rounded-lg flex items-center">
                    <button type="button" @click="tab = 'live'"
                        class="px-3.5 py-1.5 rounded-md text-xs font-semibold transition-colors"
                        :class="tab === 'live' ? 'bg-white text-primary-700 shadow-sm' : 'text-tertiary-500 hover:text-tertiary-700'">
                        Live &amp; Upcoming
                    </button>
                    <button type="button" @click="tab = 'past'"
                        class="px-3.5 py-1.5 rounded-md text-xs font-semibold transition-colors"
                        :class="tab === 'past' ? 'bg-white text-primary-700 shadow-sm' : 'text-tertiary-500 hover:text-tertiary-700'">
                        Past Sessions
                    </button>
                </div>
                <a v-if="canViewReports" :href="route('organizations.reports.attendance', organization.id)"
                    class="inline-flex items-center gap-1.5 rounded-md border border-neutral-200 px-3.5 py-2 text-xs font-semibold text-tertiary-600 hover:bg-neutral-50">
                    <ArrowDownTrayIcon class="w-3.5 h-3.5" /> Export CSV
                </a>
                <PrimaryButton v-if="canManage" type="button" @click="showNewSessionModal = true">
                    <PlusIcon class="w-4 h-4 mr-1.5" /> New Session
                </PrimaryButton>
            </div>
        </div>

        <div v-if="canManage" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard :icon="ChartBarIcon" label="Average Attendance"
                :value="kpis.average_attendance !== null ? `${kpis.average_attendance}%` : '—'"
                :caption="kpis.average_attendance !== null ? 'Across all sessions' : 'No sessions recorded yet'" />
            <StatCard :icon="RadioIcon" tone="secondary" label="Active Live Sessions" :value="kpis.active_live_sessions"
                :caption="kpis.active_live_sessions > 0 ? 'Happening today' : 'Nothing live right now'" />
            <StatCard :icon="ClipboardDocumentCheckIcon" :tone="kpis.pending_excuse_count > 0 ? 'secondary' : 'neutral'"
                label="Pending Excuse Reviews" :value="kpis.pending_excuse_count"
                :caption="kpis.pending_excuse_count > 0 ? 'Action needed' : 'All caught up'" />
            <StatCard v-if="canViewReports && eligibility" :icon="ShieldCheckIcon" tone="primary" label="Eligibility Standing"
                :value="eligibility.total_with_data > 0 ? `${eligibility.eligible}/${eligibility.total_with_data}` : '—'"
                :caption="eligibility.total_with_data > 0 ? `At or above ${eligibility.threshold}% attendance` : 'No attendance data yet'" />
        </div>

        <div v-if="myQrToken" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 p-5 mb-6 flex items-center gap-5">
            <div class="bg-white p-2 rounded-lg border border-neutral-200 shrink-0">
                <canvas ref="myQrCanvas"></canvas>
            </div>
            <div>
                <h2 class="flex items-center gap-1.5 font-heading font-bold text-tertiary-900 mb-1">
                    <QrCodeIcon class="w-4 h-4 text-primary-500" /> My QR Code
                </h2>
                <p class="text-xs text-tertiary-400">An officer can scan this to mark you present at an open session — no need to find your phone.</p>
            </div>
        </div>

        <div class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 divide-y divide-neutral-100">
            <Link v-for="session in filteredSessions" :key="session.id" :href="route('organizations.attendance.show', [organization.id, session.id])"
                class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-neutral-50">
                <div>
                    <p class="text-sm font-bold text-tertiary-900 flex items-center gap-1.5">
                        {{ session.title }}
                        <LockClosedIcon v-if="session.is_closed" class="w-3.5 h-3.5 text-tertiary-300" />
                        <span v-if="session.is_live" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-secondary-100 text-secondary-700 text-[10px] font-bold uppercase tracking-wide">
                            <span class="w-1.5 h-1.5 rounded-full bg-secondary-500 animate-pulse" /> Live
                        </span>
                    </p>
                    <p class="text-xs text-tertiary-400 mt-0.5">{{ session.session_date }}</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-semibold shrink-0">
                    <span class="text-emerald-600">{{ session.present_count }} Present</span>
                    <span class="text-secondary-600">{{ session.excused_count }} Excused</span>
                    <span class="text-tertiary-400">{{ session.absent_count }} Absent</span>
                </div>
            </Link>
            <p v-if="!filteredSessions.length" class="px-5 py-10 text-center text-sm text-tertiary-400">
                {{ tab === 'live' ? 'No live or upcoming sessions.' : 'No past sessions yet.' }}
            </p>
        </div>

        <NewSessionModal :show="showNewSessionModal" :organization-id="organization.id" :events="events" @close="showNewSessionModal = false" />
    </AuthenticatedLayout>
</template>
