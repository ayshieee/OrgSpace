<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { getInitials, colorForId } from '@/utils/initials';

const props = defineProps({
    organizationId: { type: String, required: true },
    member: { type: Object, required: true },
    roles: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
    isSelf: { type: Boolean, default: false },
});
const emit = defineEmits(['close']);

const RADIUS = 42;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

const rate = computed(() => props.member.attendance_rate);
const dashOffset = computed(() => CIRCUMFERENCE * (1 - (rate.value ?? 0) / 100));

const gaugeColor = computed(() => {
    if (rate.value === null) return '#CBD0E0';
    if (rate.value >= 90) return '#059669';
    if (rate.value >= 75) return '#FFD217';
    return '#DC2626';
});

function changeRole(roleId) {
    router.patch(route('organizations.members.update-role', [props.organizationId, props.member.id]), { role_id: roleId || null }, {
        preserveScroll: true,
    });
}

const sectionInput = ref(props.member.section ?? '');
watch(() => props.member.id, () => { sectionInput.value = props.member.section ?? ''; });

function saveSection() {
    if (sectionInput.value === (props.member.section ?? '')) return;
    router.patch(route('organizations.members.update-section', [props.organizationId, props.member.id]), { section: sectionInput.value }, {
        preserveScroll: true,
    });
}

function regenerateQr() {
    if (!confirm(`Regenerate ${props.member.name}'s QR code? Their old code will stop working.`)) return;
    router.post(route('organizations.members.regenerate-qr', [props.organizationId, props.member.id]), {}, { preserveScroll: true });
}

function resendCredentials() {
    if (!confirm(`Send ${props.member.name} a new temporary password by email? Their old password will stop working immediately.`)) return;
    router.post(route('organizations.members.resend-credentials', [props.organizationId, props.member.id]), {}, { preserveScroll: true });
}
</script>

<template>
    <div class="fixed inset-0 z-50 bg-tertiary-900/40 flex justify-end" @click.self="emit('close')">
        <div class="w-full max-w-sm bg-white h-full shadow-xl flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-200 shrink-0">
                <h2 class="font-heading font-bold text-tertiary-900">Member Details</h2>
                <button type="button" @click="emit('close')" class="text-tertiary-400 hover:text-tertiary-700">
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-5 space-y-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-sm font-bold shrink-0 overflow-hidden" :class="member.avatar_path ? '' : colorForId(member.user_id)">
                        <img v-if="member.avatar_path" :src="`/storage/${member.avatar_path}`" class="w-full h-full object-cover" :alt="member.name" />
                        <template v-else>{{ getInitials(member.name) }}</template>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-tertiary-900 truncate">{{ member.name }}<span v-if="isSelf" class="text-tertiary-400 font-medium"> (You)</span></p>
                        <p class="text-xs text-tertiary-400 truncate">{{ member.email }}</p>
                    </div>
                </div>

                <div class="flex flex-col items-center">
                    <svg width="110" height="110" viewBox="0 0 110 110" role="img" :aria-label="`Attendance rate ${rate ?? 0} percent`">
                        <circle cx="55" cy="55" :r="RADIUS" fill="none" stroke="#EEF0F7" stroke-width="10" />
                        <circle cx="55" cy="55" :r="RADIUS" fill="none" :stroke="gaugeColor" stroke-width="10"
                            stroke-linecap="round" :stroke-dasharray="CIRCUMFERENCE" :stroke-dashoffset="dashOffset"
                            transform="rotate(-90 55 55)" />
                        <text x="55" y="60" text-anchor="middle" font-size="20" font-weight="700" fill="#111827">
                            {{ rate !== null ? `${rate}%` : '—' }}
                        </text>
                    </svg>
                    <p class="text-xs text-tertiary-400 mt-1">{{ member.attendance_sessions }} session{{ member.attendance_sessions === 1 ? '' : 's' }} tracked</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-tertiary-400 mb-1.5">Role</label>
                    <select v-if="canManage" :value="member.role_id ?? ''" @change="changeRole($event.target.value)"
                        class="w-full text-sm rounded-md border-neutral-200 focus:border-primary-500 focus:ring-primary-500">
                        <option value="">No role</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                    </select>
                    <p v-else class="text-sm text-tertiary-700">{{ member.role_name ?? 'No role' }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-tertiary-400 mb-1.5">Section</label>
                    <input v-if="canManage" v-model="sectionInput" @blur="saveSection" @keyup.enter="$event.target.blur()"
                        type="text" placeholder="e.g. Soprano, Forward, Treasury Committee"
                        class="w-full text-sm rounded-md border-neutral-200 focus:border-primary-500 focus:ring-primary-500" />
                    <p v-else class="text-sm text-tertiary-700">{{ member.section ?? '—' }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-tertiary-400 mb-1.5">Student ID</label>
                    <p class="text-sm text-tertiary-700 font-mono">{{ member.membership_number ?? '—' }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-tertiary-400 mb-1.5">Joined</label>
                    <p class="text-sm text-tertiary-700">{{ member.joined_at }}</p>
                </div>

                <button v-if="canManage" type="button" @click="resendCredentials"
                    class="w-full text-xs font-semibold text-primary-600 hover:text-primary-700 border border-neutral-200 rounded-md py-2 hover:bg-neutral-50">
                    Resend Login Email
                </button>

                <button v-if="canManage" type="button" @click="regenerateQr"
                    class="w-full text-xs font-semibold text-primary-600 hover:text-primary-700 border border-neutral-200 rounded-md py-2 hover:bg-neutral-50">
                    Regenerate Attendance QR Code
                </button>
            </div>
        </div>
    </div>
</template>
