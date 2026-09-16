<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeftIcon, LockClosedIcon, QrCodeIcon, DevicePhoneMobileIcon } from '@heroicons/vue/24/outline';
import QRCode from 'qrcode';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    session: { type: Object, required: true },
    records: { type: Array, required: true },
    presentCount: { type: Number, required: true },
    checkInUrl: { type: String, required: true },
    canManage: { type: Boolean, default: false },
});

const statuses = [
    { key: 'present', label: 'Present', activeClass: 'bg-emerald-600 text-white' },
    { key: 'excused', label: 'Excused', activeClass: 'bg-secondary-500 text-white' },
    { key: 'absent', label: 'Absent', activeClass: 'bg-tertiary-400 text-white' },
];

const qrCanvas = ref(null);

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
</script>

<template>
    <Head :title="session.title" />

    <AuthenticatedLayout>
        <template #header>Attendance</template>

        <Link :href="route('organizations.attendance.index', organization.id)" class="inline-flex items-center gap-1.5 text-sm font-semibold text-tertiary-500 hover:text-tertiary-700 mb-4">
            <ArrowLeftIcon class="w-4 h-4" /> Back to Attendance
        </Link>

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="font-heading text-xl font-extrabold text-tertiary-900 flex items-center gap-2">
                    {{ session.title }}
                    <LockClosedIcon v-if="session.is_closed" class="w-4 h-4 text-tertiary-300" />
                </h1>
                <p class="text-sm text-tertiary-400">{{ session.session_date }} &middot; {{ presentCount }}/{{ records.length }} present</p>
            </div>
            <SecondaryButton v-if="canManage && !session.is_closed" @click="closeSession">Close Session</SecondaryButton>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white border border-neutral-200 rounded-xl divide-y divide-neutral-100">
                <div v-for="record in records" :key="record.id" class="flex items-center justify-between gap-4 px-5 py-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-tertiary-800 flex items-center gap-1.5">
                            {{ record.member_name }}
                            <DevicePhoneMobileIcon v-if="record.self_checked_in" title="Self check-in via QR scan" class="w-3.5 h-3.5 text-primary-500" />
                        </p>
                        <p v-if="record.marked_at" class="text-xs text-tertiary-400">{{ record.status === 'present' ? 'Checked in' : 'Marked' }} at {{ record.marked_at }}</p>
                    </div>

                    <div v-if="canManage && !session.is_closed" class="flex items-center gap-1.5 shrink-0">
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
                <p v-if="!records.length" class="px-5 py-10 text-center text-sm text-tertiary-400">No active members to mark.</p>
            </div>

            <div class="bg-white border border-neutral-200 rounded-xl p-5 text-center h-fit">
                <h2 class="flex items-center justify-center gap-1.5 font-heading font-bold text-tertiary-900 mb-1">
                    <QrCodeIcon class="w-4 h-4 text-primary-500" /> Scan to Check In
                </h2>
                <p class="text-xs text-tertiary-400 mb-4">Members scan this with their phone to mark themselves present.</p>
                <div class="inline-block bg-white p-3 rounded-lg border border-neutral-200">
                    <canvas ref="qrCanvas"></canvas>
                </div>
                <p v-if="session.is_closed" class="mt-3 text-xs text-tertiary-400">Session is closed — the code no longer accepts check-ins.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
