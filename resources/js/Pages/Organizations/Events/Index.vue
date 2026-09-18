<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusIcon, PencilSquareIcon, TrashIcon, MapPinIcon, CalendarDaysIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AnnouncementBody from '@/Components/Organizations/Announcements/AnnouncementBody.vue';
import NewEventModal from '@/Components/Organizations/Events/NewEventModal.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    upcoming: { type: Array, required: true },
    past: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
    canManageAttendance: { type: Boolean, default: false },
    attendanceModuleEnabled: { type: Boolean, default: false },
});

const showModal = ref(false);
const editingEvent = ref(null);

function startCreate() {
    editingEvent.value = null;
    showModal.value = true;
}

function startEdit(event) {
    editingEvent.value = event;
    showModal.value = true;
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
            <PrimaryButton v-if="canManage" type="button" @click="startCreate">
                <PlusIcon class="w-4 h-4 mr-1.5" /> New Event
            </PrimaryButton>
        </div>

        <section class="mb-8">
            <h2 class="text-xs font-bold uppercase tracking-wide text-tertiary-400 mb-3">Upcoming</h2>
            <div class="space-y-3">
                <div v-for="event in upcoming" :key="event.id" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 overflow-hidden">
                    <img v-if="event.image_url" :src="event.image_url" class="w-full h-40 object-cover" :alt="event.title" />
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-lg bg-primary-50 text-primary-500 flex items-center justify-center shrink-0">
                                    <CalendarDaysIcon class="w-5 h-5" />
                                </div>
                                <div class="min-w-0">
                                    <Link :href="route('organizations.events.show', [organization.id, event.id])"
                                        class="text-sm font-bold text-tertiary-900 hover:text-primary-600 transition-colors">
                                        {{ event.title }}
                                    </Link>
                                    <p class="text-xs text-tertiary-500 mt-0.5">{{ event.starts_at }}</p>
                                    <p v-if="event.location" class="flex items-center gap-1 text-xs text-tertiary-400 mt-0.5">
                                        <MapPinIcon class="w-3.5 h-3.5" /> {{ event.location }}
                                    </p>
                                    <AnnouncementBody v-if="event.description" :html="event.description" class="mt-1.5" />
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
                </div>
                <p v-if="!upcoming.length" class="text-center text-sm text-tertiary-400 py-8 bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300">
                    No upcoming events.
                </p>
            </div>
        </section>

        <section v-if="past.length">
            <h2 class="text-xs font-bold uppercase tracking-wide text-tertiary-400 mb-3">Past</h2>
            <div class="space-y-3">
                <div v-for="event in past" :key="event.id" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 p-4 opacity-70">
                    <Link :href="route('organizations.events.show', [organization.id, event.id])"
                        class="text-sm font-bold text-tertiary-700 hover:text-primary-600 transition-colors">
                        {{ event.title }}
                    </Link>
                    <p class="text-xs text-tertiary-400 mt-0.5">{{ event.starts_at }}</p>
                </div>
            </div>
        </section>

        <NewEventModal v-if="canManage" :show="showModal" :organization-id="organization.id" :editing="editingEvent"
            :can-manage-attendance="canManageAttendance" :attendance-module-enabled="attendanceModuleEnabled"
            @close="showModal = false" />
    </AuthenticatedLayout>
</template>
