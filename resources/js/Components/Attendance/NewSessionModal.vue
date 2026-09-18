<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { QrCodeIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    organizationId: { type: String, required: true },
    events: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const form = useForm({
    title: '',
    description: '',
    session_date: '',
    start_time: '',
    end_time: '',
    event_id: '',
});

watch(() => props.show, (show) => {
    if (!show) return;
    form.clearErrors();
});

function submit() {
    form.post(route('organizations.attendance.store', props.organizationId), {
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
}
</script>

<template>
    <Modal :show="show" max-width="lg" @close="emit('close')">
        <form @submit.prevent="submit" class="p-6 space-y-5">
            <div>
                <h2 class="font-heading text-lg font-bold text-tertiary-900">New Attendance Session</h2>
                <p class="mt-0.5 text-sm text-tertiary-500">Configure the session, then generate its check-in QR code.</p>
            </div>

            <div>
                <InputLabel for="new-session-title" value="Session / Event Name" />
                <TextInput id="new-session-title" v-model="form.title" type="text" class="mt-1 block w-full" required autofocus placeholder="e.g. Weekly Rehearsal" />
                <InputError :message="form.errors.title" class="mt-1" />
            </div>

            <div>
                <InputLabel for="new-session-description" value="Description (optional)" />
                <textarea id="new-session-description" v-model="form.description" rows="2"
                    class="mt-1 block w-full rounded-md border-tertiary-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    placeholder="Any details members should know before checking in..."></textarea>
                <InputError :message="form.errors.description" class="mt-1" />
            </div>

            <div>
                <InputLabel for="new-session-date" value="Date" />
                <TextInput id="new-session-date" v-model="form.session_date" type="date" class="mt-1 block w-full" required />
                <InputError :message="form.errors.session_date" class="mt-1" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="new-session-start" value="Start Time (optional)" />
                    <TextInput id="new-session-start" v-model="form.start_time" type="time" class="mt-1 block w-full" />
                    <InputError :message="form.errors.start_time" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="new-session-end" value="End Time (optional)" />
                    <TextInput id="new-session-end" v-model="form.end_time" type="time" class="mt-1 block w-full" />
                    <InputError :message="form.errors.end_time" class="mt-1" />
                    <p class="mt-1 text-xs text-tertiary-400">The QR stops accepting check-ins after this time.</p>
                </div>
            </div>

            <div v-if="events.length">
                <InputLabel for="new-session-event" value="Linked Event (optional)" />
                <select id="new-session-event" v-model="form.event_id"
                    class="mt-1 block w-full rounded-md border-tertiary-200 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">None</option>
                    <option v-for="e in events" :key="e.id" :value="e.id">{{ e.title }}</option>
                </select>
            </div>

            <div class="flex items-center gap-2.5 rounded-lg border border-neutral-200 bg-neutral-50 px-3.5 py-2.5">
                <QrCodeIcon class="w-4 h-4 text-primary-500 shrink-0" />
                <p class="text-xs font-semibold text-tertiary-600">Attendance Method: QR Code</p>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <SecondaryButton type="button" @click="emit('close')">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing">Create Session</PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
