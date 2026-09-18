<script setup>
import { ref } from 'vue';
import { QrCodeIcon, ExclamationTriangleIcon, CheckIcon } from '@heroicons/vue/24/outline';
import { useQrScanner } from '@/composables/useQrScanner';

const props = defineProps({
    organizationId: { type: String, required: true },
    sessionId: { type: String, required: true },
});
const emit = defineEmits(['scanned']);

const scanning = ref(false);
const result = ref(null);
const submitting = ref(false);

async function handleDecode(token) {
    if (submitting.value) return;
    submitting.value = true;

    try {
        const { data } = await window.axios.post(
            route('organizations.attendance.scan', [props.organizationId, props.sessionId]),
            { token }
        );
        result.value = { ok: true, ...data, at: Date.now() };
        emit('scanned');
    } catch (err) {
        result.value = {
            ok: false,
            message: err.response?.data?.message ?? 'Could not record attendance for that code.',
            at: Date.now(),
        };
    } finally {
        submitting.value = false;
    }
}

const { videoRef, error, ready } = useQrScanner(handleDecode, scanning);
</script>

<template>
    <div class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 p-5">
        <h3 class="font-heading font-bold text-tertiary-900 mb-1">Scan Member QR Code</h3>
        <p class="text-xs text-tertiary-400 mb-3">Position a member's personal QR code inside the frame.</p>

        <button v-if="!scanning" type="button" @click="scanning = true"
            class="flex w-full items-center justify-center gap-1.5 rounded-lg bg-gradient-to-b from-secondary-400 to-secondary-500 py-2.5 text-sm font-semibold text-tertiary-900 shadow-soft transition-all duration-200 ease-ios hover:shadow-elevated hover:from-secondary-300 hover:to-secondary-400 active:scale-[0.98]">
            <QrCodeIcon class="w-4 h-4" /> Start Camera
        </button>
        <div v-else class="relative aspect-video w-full overflow-hidden rounded-lg bg-tertiary-900">
            <video ref="videoRef" class="h-full w-full object-cover" muted playsinline></video>
            <div class="pointer-events-none absolute inset-8 rounded-lg border-2 border-white/70" />
            <div v-if="!ready && !error" class="absolute inset-0 flex items-center justify-center text-xs text-white/80">
                Requesting camera access…
            </div>
            <div v-if="error" class="absolute inset-0 flex flex-col items-center justify-center gap-1 px-6 text-center text-xs text-white/90">
                <ExclamationTriangleIcon class="w-5 h-5" />
                <div>{{ error }}</div>
            </div>
            <button type="button" @click="scanning = false"
                class="absolute bottom-2 right-2 text-[10px] font-bold uppercase tracking-wide bg-white/90 text-tertiary-700 px-2 py-1 rounded">
                Stop
            </button>
        </div>

        <div v-if="result" class="mt-4 rounded-lg p-4 text-center"
            :class="result.ok ? (result.already_present ? 'border border-secondary-200 bg-secondary-50' : 'border border-emerald-200 bg-emerald-50') : 'border border-red-200 bg-red-50'">
            <template v-if="result.ok">
                <div class="mb-1.5 flex justify-center" :class="result.already_present ? 'text-secondary-600' : 'text-emerald-600'">
                    <CheckIcon class="w-5 h-5" />
                </div>
                <h4 class="text-sm font-bold text-tertiary-900">{{ result.member_name }}</h4>
                <p class="text-xs text-tertiary-500">
                    {{ result.already_present ? 'Already marked present' : 'Marked present' }} at {{ result.marked_at }}
                </p>
            </template>
            <template v-else>
                <div class="mb-1.5 flex justify-center text-red-600"><ExclamationTriangleIcon class="w-5 h-5" /></div>
                <h4 class="text-sm font-bold text-tertiary-900">Not recorded</h4>
                <p class="text-xs text-tertiary-500">{{ result.message }}</p>
            </template>
        </div>
    </div>
</template>
