<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { PlusIcon, LockClosedIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    sessions: { type: Array, required: true },
    events: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
});

const showForm = ref(false);
const form = useForm({ title: '', session_date: '', event_id: '' });

function submit() {
    form.post(route('organizations.attendance.store', props.organization.id), {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Attendance" />

    <AuthenticatedLayout>
        <template #header>Attendance</template>

        <div class="flex items-center justify-between mb-5">
            <h1 class="font-heading text-xl font-extrabold text-tertiary-900">Attendance</h1>
            <PrimaryButton v-if="canManage && !showForm" type="button" @click="showForm = true">
                <PlusIcon class="w-4 h-4 mr-1.5" /> New Session
            </PrimaryButton>
        </div>

        <form v-if="showForm" @submit.prevent="submit" class="bg-white border border-neutral-200 rounded-xl p-5 mb-6 space-y-4">
            <div>
                <label for="title" class="block text-sm font-medium text-tertiary-700">Session Title</label>
                <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" placeholder="e.g. Weekly Rehearsal" required autofocus />
                <InputError :message="form.errors.title" class="mt-1" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="session_date" class="block text-sm font-medium text-tertiary-700">Date</label>
                    <TextInput id="session_date" v-model="form.session_date" type="date" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.session_date" class="mt-1" />
                </div>
                <div>
                    <label for="event_id" class="block text-sm font-medium text-tertiary-700">Linked Event (optional)</label>
                    <select id="event_id" v-model="form.event_id"
                        class="mt-1 block w-full rounded-md border-tertiary-200 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">None</option>
                        <option v-for="e in events" :key="e.id" :value="e.id">{{ e.title }}</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <PrimaryButton :disabled="form.processing">Create & Start Marking</PrimaryButton>
                <button type="button" @click="showForm = false" class="rounded-md border border-neutral-200 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-tertiary-600 hover:bg-neutral-100">Cancel</button>
            </div>
        </form>

        <div class="bg-white border border-neutral-200 rounded-xl divide-y divide-neutral-100">
            <Link v-for="session in sessions" :key="session.id" :href="route('organizations.attendance.show', [organization.id, session.id])"
                class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-neutral-50">
                <div>
                    <p class="text-sm font-bold text-tertiary-900 flex items-center gap-1.5">
                        {{ session.title }}
                        <LockClosedIcon v-if="session.is_closed" class="w-3.5 h-3.5 text-tertiary-300" />
                    </p>
                    <p class="text-xs text-tertiary-400 mt-0.5">{{ session.session_date }}</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-semibold shrink-0">
                    <span class="text-emerald-600">{{ session.present_count }} Present</span>
                    <span class="text-secondary-600">{{ session.excused_count }} Excused</span>
                    <span class="text-tertiary-400">{{ session.absent_count }} Absent</span>
                </div>
            </Link>
            <p v-if="!sessions.length" class="px-5 py-10 text-center text-sm text-tertiary-400">No attendance sessions yet.</p>
        </div>
    </AuthenticatedLayout>
</template>
