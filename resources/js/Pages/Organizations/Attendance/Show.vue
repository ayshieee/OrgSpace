<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeftIcon, LockClosedIcon, QrCodeIcon, DevicePhoneMobileIcon, CameraIcon, ListBulletIcon,
    MegaphoneIcon, CheckCircleIcon, XCircleIcon, ArrowUturnLeftIcon, XMarkIcon,
} from '@heroicons/vue/24/outline';
import QRCode from 'qrcode';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import QrScannerView from '@/Components/Attendance/QrScannerView.vue';
import { getInitials, colorForId } from '@/utils/initials';

const props = defineProps({
    organization: { type: Object, required: true },
    session: { type: Object, required: true },
    records: { type: Array, required: true },
    presentCount: { type: Number, required: true },
    sectionBreakdown: { type: Array, default: () => [] },
    checkInUrl: { type: String, required: true },
    canManage: { type: Boolean, default: false },
});

const statuses = [
    { key: 'present', label: 'Present', activeClass: 'bg-emerald-600 text-white' },
    { key: 'excused', label: 'Excused', activeClass: 'bg-secondary-500 text-tertiary-900' },
    { key: 'absent', label: 'Absent', activeClass: 'bg-tertiary-400 text-white' },
];

// Mirrors the backend's real lifecycle gate (AttendanceController::abortIfSessionUnavailable)
// so mark/scan/excuse controls don't stay clickable for a session the
// server will now reject mutations on.
const isUnavailable = computed(() => ['ended', 'expired'].includes(props.session.status));

const statusBadges = {
    scheduled: { label: 'Scheduled', classes: 'bg-neutral-100 text-tertiary-500' },
    active: { label: 'Live', classes: 'bg-secondary-100 text-secondary-700' },
    ended: { label: 'Ended', classes: 'bg-tertiary-100 text-tertiary-500' },
    expired: { label: 'Expired', classes: 'bg-red-50 text-red-600' },
};
const statusBadge = computed(() => statusBadges[props.session.status] ?? statusBadges.ended);

const rosterTab = ref('manual');
const selectedRecordId = ref(null);
const selectedRecord = computed(() => props.records.find((r) => r.id === selectedRecordId.value) ?? null);

const qrCanvas = ref(null);

function onScanned() {
    router.reload({ only: ['records', 'presentCount'], preserveScroll: true });
}

function renderQr() {
    if (!qrCanvas.value) return;
    QRCode.toCanvas(qrCanvas.value, props.checkInUrl, {
        width: 176,
        margin: 1,
        color: { dark: '#111827', light: '#FFFFFF' },
    });
}

onMounted(renderQr);
watch(() => props.checkInUrl, renderQr);

function mark(record, status) {
    router.patch(route('organizations.attendance.records.update', [props.organization.id, props.session.id, record.id]), { status }, {
        preserveScroll: true,
    });
}

function closeSession() {
    if (!confirm('Close this session? No further changes can be made.')) return;
    router.post(route('organizations.attendance.close', [props.organization.id, props.session.id]), {}, { preserveScroll: true });
}

function broadcastReminder() {
    router.post(route('organizations.attendance.broadcast', [props.organization.id, props.session.id]), {}, { preserveScroll: true });
}

function reviewExcuse(decision) {
    if (!selectedRecord.value) return;
    router.post(route('organizations.attendance.excuse.review', [props.organization.id, props.session.id, selectedRecord.value.id]), { decision }, {
        preserveScroll: true,
    });
}

// Real elapsed-time display for a live session, driven by the session's
// actual creation timestamp — not a simulated/hardcoded counter.
const elapsed = ref('');
let elapsedTimer = null;

function updateElapsed() {
    const startedAt = new Date(props.session.started_at).getTime();
    const diffSeconds = Math.max(0, Math.floor((Date.now() - startedAt) / 1000));
    const h = Math.floor(diffSeconds / 3600);
    const m = Math.floor((diffSeconds % 3600) / 60);
    const s = diffSeconds % 60;
    elapsed.value = h > 0 ? `${h}h ${m}m ${s}s` : `${m}m ${s}s`;
}

onMounted(() => {
    if (props.session.is_live) {
        updateElapsed();
        elapsedTimer = setInterval(updateElapsed, 1000);
    }
});
onUnmounted(() => {
    if (elapsedTimer) clearInterval(elapsedTimer);
});

// Self-service excuse submission (any active member, on their own record).
const excuseForm = useForm({ note: '' });
const submittingExcuseFor = ref(null);

function submitExcuse(record) {
    excuseForm.post(route('organizations.attendance.excuse.submit', [props.organization.id, props.session.id, record.id]), {
        preserveScroll: true,
        onSuccess: () => {
            excuseForm.reset();
            submittingExcuseFor.value = null;
        },
    });
}
</script>

<template>
    <Head :title="session.title" />

    <AuthenticatedLayout>
        <template #header>Attendance</template>

        <Link :href="route('organizations.attendance.index', organization.id)" class="inline-flex items-center gap-1.5 text-sm font-semibold text-tertiary-500 hover:text-tertiary-700 mb-4">
            <ArrowLeftIcon class="w-4 h-4" /> Back to Attendance
        </Link>

        <!-- Live session banner -->
        <div v-if="session.is_live" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 p-5 mb-5">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-secondary-100 text-secondary-700 text-[10px] font-bold uppercase tracking-wide">
                        <span class="w-1.5 h-1.5 rounded-full bg-secondary-500 animate-pulse" /> Live now
                    </span>
                    <h2 class="font-heading text-lg font-bold text-tertiary-900 mt-1.5">{{ session.title }}</h2>
                    <p class="text-xs text-tertiary-400 mt-0.5">Elapsed: <strong class="text-tertiary-700 font-semibold">{{ elapsed }}</strong></p>
                </div>
                <div v-if="canManage" class="flex items-center gap-2">
                    <button type="button" @click="broadcastReminder"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md bg-neutral-100 hover:bg-neutral-200 text-tertiary-700 text-xs font-semibold transition-colors">
                        <MegaphoneIcon class="w-4 h-4 text-secondary-600" /> Broadcast Check-in Reminder
                    </button>
                    <SecondaryButton @click="closeSession">Close Session</SecondaryButton>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="font-heading text-xl font-extrabold text-tertiary-900 flex items-center gap-2">
                    {{ session.title }}
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide" :class="statusBadge.classes">{{ statusBadge.label }}</span>
                    <LockClosedIcon v-if="isUnavailable" class="w-4 h-4 text-tertiary-300" />
                </h1>
                <p v-if="session.description" class="text-sm text-tertiary-500 mt-0.5">{{ session.description }}</p>
                <p class="text-sm text-tertiary-400">
                    {{ session.session_date }}
                    <template v-if="session.start_time || session.end_time"> &middot; {{ session.start_time ?? '—' }}–{{ session.end_time ?? '—' }}</template>
                    &middot; {{ presentCount }}/{{ records.length }} present
                </p>
            </div>
            <SecondaryButton v-if="canManage && !isUnavailable && !session.is_live" @click="closeSession">Close Session</SecondaryButton>
        </div>

        <div v-if="sectionBreakdown.length" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 p-5 mb-6">
            <h2 class="font-heading font-bold text-tertiary-900 mb-3">Section Check-in</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div v-for="s in sectionBreakdown" :key="s.section" class="p-3 rounded-lg bg-neutral-50">
                    <p class="text-xs font-semibold text-tertiary-500">{{ s.section }}</p>
                    <p class="text-sm font-bold text-tertiary-900 mt-0.5">{{ s.present }} / {{ s.total }} present</p>
                    <div class="w-full bg-neutral-200 h-1 rounded-full mt-2 overflow-hidden">
                        <div class="bg-primary h-full" :style="{ width: `${s.total ? (s.present / s.total) * 100 : 0}%` }" />
                    </div>
                </div>
            </div>
        </div>

        <div v-if="canManage && !isUnavailable" class="flex items-center gap-1.5 mb-4">
            <button type="button" @click="rosterTab = 'manual'"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold"
                :class="rosterTab === 'manual' ? 'bg-primary-50 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">
                <ListBulletIcon class="w-3.5 h-3.5" /> Manual
            </button>
            <button type="button" @click="rosterTab = 'scan'"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold"
                :class="rosterTab === 'scan' ? 'bg-primary-50 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">
                <CameraIcon class="w-3.5 h-3.5" /> Scan
            </button>
        </div>

        <div v-if="rosterTab === 'scan' && canManage && !isUnavailable" class="mb-6 max-w-md">
            <QrScannerView :organization-id="organization.id" :session-id="session.id" @scanned="onScanned" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div v-if="rosterTab === 'manual' || !canManage || isUnavailable" class="lg:col-span-2 bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 divide-y divide-neutral-100">
                <div v-for="record in records" :key="record.id" class="px-5 py-3"
                    :class="canManage ? 'cursor-pointer hover:bg-neutral-50' : ''"
                    @click="canManage && (selectedRecordId = record.id)">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 overflow-hidden" :class="record.avatar_path ? '' : colorForId(record.user_id)">
                                <img v-if="record.avatar_path" :src="`/storage/${record.avatar_path}`" class="w-full h-full object-cover" :alt="record.member_name" />
                                <template v-else>{{ getInitials(record.member_name) }}</template>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-tertiary-800 flex items-center gap-1.5 truncate">
                                    {{ record.member_name }}
                                    <DevicePhoneMobileIcon v-if="record.self_checked_in" title="Self check-in via QR scan" class="w-3.5 h-3.5 text-primary-500 shrink-0" />
                                    <span v-if="record.excuse_status === 'pending'" class="text-[10px] font-bold uppercase tracking-wide bg-secondary-100 text-secondary-700 px-1.5 py-0.5 rounded shrink-0">Excuse pending</span>
                                </p>
                                <p class="text-xs text-tertiary-400 truncate">
                                    <span v-if="record.section">{{ record.section }} &middot; </span>{{ record.membership_number ?? record.member_email }}
                                </p>
                            </div>
                        </div>

                        <div v-if="canManage && !isUnavailable" class="flex items-center gap-1.5 shrink-0" @click.stop>
                            <button v-for="s in statuses" :key="s.key" type="button" @click="mark(record, s.key)"
                                class="px-2.5 py-1 rounded-md text-xs font-semibold border transition-colors"
                                :class="record.status === s.key ? `${s.activeClass} border-transparent` : 'border-neutral-200 text-tertiary-500 hover:bg-neutral-50'">
                                {{ s.label }}
                            </button>
                        </div>
                        <span v-else class="px-2.5 py-1 rounded-full text-xs font-semibold shrink-0"
                            :class="{
                                'bg-emerald-100 text-emerald-700': record.status === 'present',
                                'bg-secondary-100 text-secondary-700': record.status === 'excused',
                                'bg-neutral-100 text-tertiary-500': record.status === 'absent',
                            }">
                            {{ statuses.find((s) => s.key === record.status)?.label }}
                        </span>
                    </div>

                    <!-- Self-service excuse submission, own record only -->
                    <div v-if="record.is_mine && !canManage && record.status !== 'present'" class="mt-2 pl-11">
                        <button v-if="submittingExcuseFor !== record.id && record.excuse_status !== 'pending'" type="button"
                            @click.stop="submittingExcuseFor = record.id"
                            class="text-xs font-semibold text-primary-600 hover:text-primary-700">
                            Submit an excuse
                        </button>
                        <p v-else-if="record.excuse_status === 'pending'" class="text-xs text-tertiary-400 italic">
                            Your excuse was submitted {{ record.excuse_submitted_at }} — awaiting review.
                        </p>
                        <div v-else class="mt-1 flex flex-col gap-2" @click.stop>
                            <textarea v-model="excuseForm.note" rows="2" placeholder="Explain what happened…"
                                class="w-full text-sm rounded-md border-neutral-200 focus:border-primary-500 focus:ring-primary-500" />
                            <div class="flex items-center gap-2">
                                <button type="button" @click="submitExcuse(record)" :disabled="excuseForm.processing || !excuseForm.note"
                                    class="text-xs font-semibold text-tertiary-900 bg-gradient-to-b from-secondary-400 to-secondary-500 px-3 py-1.5 rounded-lg shadow-sm transition-all duration-200 ease-ios hover:shadow-soft hover:from-secondary-300 hover:to-secondary-400 disabled:opacity-50">
                                    Submit
                                </button>
                                <button type="button" @click="submittingExcuseFor = null" class="text-xs font-semibold text-tertiary-500 hover:text-tertiary-700">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-if="!records.length" class="px-5 py-10 text-center text-sm text-tertiary-400">No active members to mark.</p>
            </div>

            <div class="flex flex-col gap-4">
                <!-- Excuse review / member detail panel -->
                <div v-if="selectedRecord" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-heading font-bold text-tertiary-900">Member Detail</h2>
                        <button type="button" @click="selectedRecordId = null" class="text-tertiary-400 hover:text-tertiary-700">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-11 h-11 rounded-full flex items-center justify-center text-sm font-bold shrink-0 overflow-hidden" :class="selectedRecord.avatar_path ? '' : colorForId(selectedRecord.user_id)">
                            <img v-if="selectedRecord.avatar_path" :src="`/storage/${selectedRecord.avatar_path}`" class="w-full h-full object-cover" :alt="selectedRecord.member_name" />
                            <template v-else>{{ getInitials(selectedRecord.member_name) }}</template>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-tertiary-900 truncate">{{ selectedRecord.member_name }}</p>
                            <p class="text-xs text-tertiary-400 truncate">{{ selectedRecord.section ? selectedRecord.section + ' · ' : '' }}{{ selectedRecord.member_email }}</p>
                        </div>
                    </div>

                    <template v-if="selectedRecord.excuse_note">
                        <p class="text-xs font-bold uppercase tracking-wide text-tertiary-400 mb-1.5">Submitted Excuse</p>
                        <div class="p-3.5 rounded-lg bg-neutral-50 text-sm text-tertiary-700 leading-relaxed mb-2">
                            <p class="italic">"{{ selectedRecord.excuse_note }}"</p>
                            <p class="text-xs text-tertiary-400 mt-2">Submitted {{ selectedRecord.excuse_submitted_at }}</p>
                        </div>

                        <div v-if="selectedRecord.excuse_status === 'pending' && canManage && !isUnavailable" class="flex flex-col gap-2 mt-3">
                            <button type="button" @click="reviewExcuse('approve_excused')"
                                class="w-full py-2 px-3 rounded-md bg-secondary-100 hover:bg-secondary-200 text-secondary-800 text-xs font-bold flex items-center justify-center gap-1.5">
                                <CheckCircleIcon class="w-4 h-4" /> Approve as Excused
                            </button>
                            <button type="button" @click="reviewExcuse('convert_present')"
                                class="w-full py-2 px-3 rounded-md bg-gradient-to-b from-secondary-400 to-secondary-500 hover:from-secondary-300 hover:to-secondary-400 text-tertiary-900 text-xs font-semibold shadow-sm hover:shadow-soft transition-all duration-200 ease-ios flex items-center justify-center gap-1.5">
                                <CheckCircleIcon class="w-4 h-4" /> Convert to Present
                            </button>
                            <button type="button" @click="reviewExcuse('deny')"
                                class="w-full py-1.5 px-3 rounded-md text-red-600 hover:bg-red-50 text-xs font-semibold flex items-center justify-center gap-1.5">
                                <XCircleIcon class="w-4 h-4" /> Deny — Keep {{ statuses.find((s) => s.key === selectedRecord.status)?.label }}
                            </button>
                        </div>
                        <p v-else-if="selectedRecord.excuse_status !== 'pending'" class="text-xs text-tertiary-400 flex items-center gap-1.5 mt-2">
                            <ArrowUturnLeftIcon class="w-3.5 h-3.5" />
                            {{ selectedRecord.excuse_status === 'approved' ? 'Approved' : 'Denied' }} by {{ selectedRecord.excuse_reviewed_by_name }} on {{ selectedRecord.excuse_reviewed_at }}
                        </p>
                    </template>
                    <p v-else class="text-xs text-tertiary-400">No excuse submitted for this record.</p>
                </div>

                <!-- Check-in QR -->
                <div class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 p-5 text-center">
                    <h2 class="flex items-center justify-center gap-1.5 font-heading font-bold text-tertiary-900 mb-1">
                        <QrCodeIcon class="w-4 h-4 text-primary-500" /> Scan to Check In
                    </h2>
                    <p class="text-xs text-tertiary-400 mb-4">Members scan this with their phone to mark themselves present.</p>
                    <div class="inline-block bg-white p-3 rounded-lg border border-neutral-200">
                        <canvas ref="qrCanvas"></canvas>
                    </div>
                    <p v-if="isUnavailable" class="mt-3 text-xs text-tertiary-400">Session {{ statusBadge.label.toLowerCase() }} — the code no longer accepts check-ins.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
