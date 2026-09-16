<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { PlusIcon, PencilSquareIcon, TrashIcon, MapPinIcon, CalendarDaysIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    upcoming: { type: Array, required: true },
    past: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
});

const showForm = ref(false);
const editingId = ref(null);

const form = useForm({ title: '', description: '', location: '', starts_at: '', ends_at: '' });

function startCreate() {
    editingId.value = null;
    form.reset();
    showForm.value = true;
}

function startEdit(event) {
    editingId.value = event.id;
    form.title = event.title;
    form.description = event.description ?? '';
    form.location = event.location ?? '';
    form.starts_at = event.starts_at_input;
    form.ends_at = event.ends_at_input ?? '';
    showForm.value = true;
}

function submit() {
    if (editingId.value) {
        form.patch(route('organizations.events.update', [props.organization.id, editingId.value]), {
            preserveScroll: true,
            onSuccess: () => (showForm.value = false),
        });
    } else {
        form.post(route('organizations.events.store', props.organization.id), {
            preserveScroll: true,
            onSuccess: () => (showForm.value = false),
        });
    }
}

function destroy(event) {
    if (!confirm(`Delete "${event.title}"?`)) return;
    router.delete(route('organizations.events.destroy', [props.organization.id, event.id]), { preserveScroll: true });
}

const expandedRsvps = ref(null);
function toggleRsvpList(id) {
    expandedRsvps.value = expandedRsvps.value === id ? null : id;
}

const rsvpOptions = [
    { key: 'going', label: 'Going' },
    { key: 'maybe', label: 'Maybe' },
    { key: 'not_going', label: "Can't Go" },
];

function rsvp(event, status) {
    router.post(route('organizations.events.rsvp', [props.organization.id, event.id]), { status }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Events & Calendar" />

    <AuthenticatedLayout>
        <template #header>Events</template>

        <div class="flex items-center justify-between mb-5">
            <h1 class="font-heading text-xl font-extrabold text-tertiary-900">Events & Calendar</h1>
            <PrimaryButton v-if="canManage && !showForm" type="button" @click="startCreate">
                <PlusIcon class="w-4 h-4 mr-1.5" /> New Event
            </PrimaryButton>
        </div>

        <form v-if="showForm" @submit.prevent="submit" class="bg-white border border-neutral-200 rounded-xl p-5 mb-6 space-y-4">
            <div>
                <label for="title" class="block text-sm font-medium text-tertiary-700">Event Title</label>
                <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" required autofocus />
                <InputError :message="form.errors.title" class="mt-1" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="starts_at" class="block text-sm font-medium text-tertiary-700">Starts</label>
                    <TextInput id="starts_at" v-model="form.starts_at" type="datetime-local" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.starts_at" class="mt-1" />
                </div>
                <div>
                    <label for="ends_at" class="block text-sm font-medium text-tertiary-700">Ends (optional)</label>
                    <TextInput id="ends_at" v-model="form.ends_at" type="datetime-local" class="mt-1 block w-full" />
                    <InputError :message="form.errors.ends_at" class="mt-1" />
                </div>
            </div>
            <div>
                <label for="location" class="block text-sm font-medium text-tertiary-700">Location (optional)</label>
                <TextInput id="location" v-model="form.location" type="text" class="mt-1 block w-full" />
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-tertiary-700">Description (optional)</label>
                <textarea id="description" v-model="form.description" rows="3"
                    class="mt-1 block w-full rounded-md border-tertiary-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
            </div>
            <div class="flex items-center gap-3">
                <PrimaryButton :disabled="form.processing">{{ editingId ? 'Save Changes' : 'Create Event' }}</PrimaryButton>
                <button type="button" @click="showForm = false" class="rounded-md border border-neutral-200 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-tertiary-600 hover:bg-neutral-100">Cancel</button>
            </div>
        </form>

        <section class="mb-8">
            <h2 class="text-xs font-bold uppercase tracking-wide text-tertiary-400 mb-3">Upcoming</h2>
            <div class="space-y-3">
                <div v-for="event in upcoming" :key="event.id" class="bg-white border border-neutral-200 rounded-xl p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-lg bg-primary-50 text-primary-500 flex items-center justify-center shrink-0">
                                <CalendarDaysIcon class="w-5 h-5" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-tertiary-900">{{ event.title }}</p>
                                <p class="text-xs text-tertiary-500 mt-0.5">{{ event.starts_at }}</p>
                                <p v-if="event.location" class="flex items-center gap-1 text-xs text-tertiary-400 mt-0.5">
                                    <MapPinIcon class="w-3.5 h-3.5" /> {{ event.location }}
                                </p>
                                <p v-if="event.description" class="text-xs text-tertiary-500 mt-1.5">{{ event.description }}</p>
                            </div>
                        </div>
                        <div v-if="canManage" class="flex items-center gap-2 shrink-0">
                            <button type="button" @click="startEdit(event)" class="text-tertiary-300 hover:text-primary-600">
                                <PencilSquareIcon class="w-4 h-4" />
                            </button>
                            <button type="button" @click="destroy(event)" class="text-tertiary-300 hover:text-red-500">
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-neutral-100">
                        <div class="flex items-center gap-1.5">
                            <button v-for="opt in rsvpOptions" :key="opt.key" type="button" @click="rsvp(event, opt.key)"
                                class="px-2.5 py-1 rounded-md text-xs font-semibold border transition-colors"
                                :class="event.my_rsvp === opt.key ? 'bg-primary border-primary text-white' : 'border-neutral-200 text-tertiary-500 hover:bg-neutral-50'">
                                {{ opt.label }}
                            </button>
                        </div>
                        <button v-if="canManage" type="button" @click="toggleRsvpList(event.id)" class="flex items-center gap-1 text-xs font-semibold text-tertiary-400 hover:text-tertiary-800">
                            {{ event.going_count }} Going &middot; {{ event.maybe_count }} Maybe
                            <ChevronDownIcon class="w-3.5 h-3.5 transition-transform" :class="expandedRsvps === event.id ? 'rotate-180' : ''" />
                        </button>
                    </div>

                    <div v-if="canManage && expandedRsvps === event.id" class="mt-3 pt-3 border-t border-neutral-100 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1.5">
                        <div v-for="r in event.rsvp_list" :key="r.name" class="flex items-center justify-between text-xs">
                            <span class="text-tertiary-600 truncate">{{ r.name }}</span>
                            <span class="shrink-0" :class="{
                                'text-emerald-600': r.status === 'going',
                                'text-secondary-600': r.status === 'maybe',
                                'text-tertiary-400': r.status === 'not_going',
                            }">{{ rsvpOptions.find((o) => o.key === r.status)?.label }}</span>
                        </div>
                        <p v-if="!event.rsvp_list.length" class="text-xs text-tertiary-400 col-span-2">No RSVPs yet.</p>
                    </div>
                </div>
                <p v-if="!upcoming.length" class="text-center text-sm text-tertiary-400 py-8 bg-white border border-neutral-200 rounded-xl">
                    No upcoming events.
                </p>
            </div>
        </section>

        <section v-if="past.length">
            <h2 class="text-xs font-bold uppercase tracking-wide text-tertiary-400 mb-3">Past</h2>
            <div class="space-y-3">
                <div v-for="event in past" :key="event.id" class="bg-white border border-neutral-200 rounded-xl p-4 opacity-70">
                    <p class="text-sm font-bold text-tertiary-700">{{ event.title }}</p>
                    <p class="text-xs text-tertiary-400 mt-0.5">{{ event.starts_at }}</p>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>
