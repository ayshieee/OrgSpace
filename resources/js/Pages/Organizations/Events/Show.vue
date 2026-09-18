<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeftIcon, MapPinIcon, CalendarDaysIcon, ClockIcon, PencilSquareIcon, MegaphoneIcon,
    PrinterIcon, TrashIcon, CalendarIcon, PlusIcon, XMarkIcon, BellAlertIcon, CheckBadgeIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AnnouncementBody from '@/Components/Organizations/Announcements/AnnouncementBody.vue';
import NewEventModal from '@/Components/Organizations/Events/NewEventModal.vue';
import { getInitials, colorForId } from '@/utils/initials';

const props = defineProps({
    organization: { type: Object, required: true },
    event: { type: Object, required: true },
    canManage: { type: Boolean, default: false },
    canManageAttendance: { type: Boolean, default: false },
    attendanceModuleEnabled: { type: Boolean, default: false },
});

const showEditModal = ref(false);

function formatTime(hhmm) {
    if (!hhmm) return null;
    const [h, m] = hhmm.split(':').map(Number);
    const d = new Date();
    d.setHours(h, m, 0, 0);
    return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
}

const rsvpOptions = [
    { key: 'going', label: 'Going' },
    { key: 'maybe', label: 'Maybe' },
    { key: 'not_going', label: "Can't Go" },
];

function rsvp(status) {
    router.post(route('organizations.events.rsvp', [props.organization.id, props.event.id]), { status }, { preserveScroll: true });
}

const clearancePct = computed(() => {
    const total = props.event.clearance.active_member_count;
    return total ? Math.round((props.event.clearance.confirmed / total) * 100) : 0;
});

function broadcastCallSheet() {
    if (!confirm(`Send the call sheet to all ${props.event.clearance.active_member_count} active members?`)) return;
    router.post(route('organizations.events.broadcast-call-sheet', [props.organization.id, props.event.id]), {}, { preserveScroll: true });
}

function nudge(member) {
    router.post(route('organizations.events.roster.nudge', [props.organization.id, props.event.id, member.member_id]), {}, { preserveScroll: true });
}

function cancelEvent() {
    if (!confirm(`Cancel "${props.event.title}"? This permanently deletes the event.`)) return;
    router.delete(route('organizations.events.destroy', [props.organization.id, props.event.id]));
}

function printRoster() {
    window.print();
}

const rsvpStatusLabels = { going: 'Going', maybe: 'Maybe', not_going: 'Not Going' };

// Checklist
const checklistForm = useForm({ label: '', status: 'pending' });
const showChecklistForm = ref(false);

const checklistStatusMeta = {
    pending: { label: 'Pending', classes: 'bg-neutral-100 text-tertiary-500' },
    in_progress: { label: 'In Progress', classes: 'bg-secondary-100 text-secondary-700' },
    cleared: { label: 'Cleared', classes: 'bg-emerald-100 text-emerald-700' },
};

function addChecklistItem() {
    checklistForm.post(route('organizations.events.checklist.store', [props.organization.id, props.event.id]), {
        preserveScroll: true,
        onSuccess: () => {
            checklistForm.reset();
            showChecklistForm.value = false;
        },
    });
}

function setChecklistStatus(item, status) {
    router.patch(route('organizations.events.checklist.update', [props.organization.id, props.event.id, item.id]), { status }, { preserveScroll: true });
}

function removeChecklistItem(item) {
    if (!confirm(`Remove "${item.label}" from the checklist?`)) return;
    router.delete(route('organizations.events.checklist.destroy', [props.organization.id, props.event.id, item.id]), { preserveScroll: true });
}
</script>

<template>
    <Head :title="event.title" />

    <AuthenticatedLayout>
        <template #header>Events</template>

        <Link :href="route('organizations.events.index', organization.id)" class="inline-flex items-center gap-1.5 text-sm font-semibold text-tertiary-500 hover:text-tertiary-700 mb-4 print:hidden">
            <ArrowLeftIcon class="w-4 h-4" /> Back to Events
        </Link>

        <!-- Header card -->
        <div class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft overflow-hidden mb-6 print:hidden">
            <img v-if="event.image_url" :src="event.image_url" class="w-full h-56 object-cover" :alt="event.title" />
            <div class="p-5">
                <div class="flex items-center flex-wrap gap-1.5 mb-2">
                    <span v-if="event.category" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-primary-100 text-primary-700">{{ event.category }}</span>
                    <span v-if="event.track_attendance" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-secondary-100 text-secondary-700">Mandatory Attendance</span>
                </div>
                <h1 class="font-heading text-xl font-extrabold text-tertiary-900">{{ event.title }}</h1>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-sm text-tertiary-500">
                    <span class="flex items-center gap-1.5"><CalendarDaysIcon class="w-4 h-4 text-tertiary-400" /> {{ event.starts_at }}</span>
                    <span v-if="event.call_time" class="flex items-center gap-1.5"><ClockIcon class="w-4 h-4 text-tertiary-400" /> Call Time {{ formatTime(event.call_time) }}</span>
                    <span v-if="event.location" class="flex items-center gap-1.5"><MapPinIcon class="w-4 h-4 text-tertiary-400" /> {{ event.location }}</span>
                </div>
                <AnnouncementBody v-if="event.description" :html="event.description" class="mt-3" />

                <div class="flex items-center justify-between mt-4 pt-4 border-t border-neutral-100">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-semibold text-tertiary-400 mr-1">Your RSVP:</span>
                        <button v-for="opt in rsvpOptions" :key="opt.key" type="button" @click="rsvp(opt.key)"
                            class="px-2.5 py-1 rounded-md text-xs font-semibold border transition-colors"
                            :class="event.my_rsvp === opt.key ? 'bg-primary border-primary text-white' : 'border-neutral-200 text-tertiary-500 hover:bg-neutral-50'">
                            {{ opt.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Roster Clearance Status -->
        <div v-if="canManage" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft p-5 mb-6 print:hidden">
            <div class="flex items-center justify-between mb-1">
                <h2 class="font-heading font-bold text-tertiary-900">Roster Clearance Status</h2>
                <span class="text-xs text-tertiary-400">{{ event.clearance.active_member_count }} Active Members</span>
            </div>
            <div class="w-full bg-neutral-100 h-2 rounded-full overflow-hidden mt-2">
                <div class="bg-primary h-full transition-all duration-300" :style="{ width: `${clearancePct}%` }" />
            </div>
            <div class="flex flex-wrap gap-x-6 gap-y-1 mt-3 text-xs">
                <span class="flex items-center gap-1.5 text-tertiary-600"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> {{ event.clearance.confirmed }} Confirmed</span>
                <span class="flex items-center gap-1.5 text-tertiary-600"><span class="w-2 h-2 rounded-full bg-tertiary-400"></span> {{ event.clearance.excused }} Excused Absences</span>
                <span class="flex items-center gap-1.5 text-tertiary-600"><span class="w-2 h-2 rounded-full bg-amber-400"></span> {{ event.clearance.awaiting }} Awaiting Response</span>
            </div>

            <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-neutral-100">
                <SecondaryButton type="button" @click="showEditModal = true">
                    <PencilSquareIcon class="w-4 h-4 mr-1.5" /> Edit Logistics
                </SecondaryButton>
                <SecondaryButton type="button" @click="broadcastCallSheet">
                    <MegaphoneIcon class="w-4 h-4 mr-1.5" /> Broadcast Call Sheet
                </SecondaryButton>
                <SecondaryButton type="button" @click="printRoster">
                    <PrinterIcon class="w-4 h-4 mr-1.5" /> Print Roster
                </SecondaryButton>
                <SecondaryButton type="button" @click="showEditModal = true">
                    <CalendarIcon class="w-4 h-4 mr-1.5" /> Postpone
                </SecondaryButton>
                <button type="button" @click="cancelEvent" class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors">
                    <TrashIcon class="w-4 h-4 mr-1.5" /> Cancel Event
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: sessions -->
            <div class="lg:col-span-2 print:hidden">
                <div class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-neutral-100">
                        <h2 class="font-heading font-bold text-tertiary-900">Upcoming Production Sessions</h2>
                    </div>
                    <div class="divide-y divide-neutral-100">
                        <Link v-for="session in event.sessions" :key="session.id" :href="session.show_url"
                            class="block px-5 py-3.5 hover:bg-neutral-50 transition-colors">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-tertiary-800 truncate">{{ session.title }}</p>
                                    <p class="text-xs text-tertiary-400 mt-0.5">
                                        {{ session.session_date }}
                                        <template v-if="session.start_time || session.end_time"> &middot; {{ session.start_time ?? '—' }}–{{ session.end_time ?? '—' }}</template>
                                        <template v-if="session.description"> &middot; {{ session.description }}</template>
                                    </p>
                                </div>
                                <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                    :class="session.is_live ? 'bg-secondary-100 text-secondary-700' : 'bg-neutral-100 text-tertiary-500'">
                                    {{ session.is_live ? 'Live' : session.status }}
                                </span>
                            </div>
                        </Link>
                        <p v-if="!event.sessions.length" class="px-5 py-8 text-center text-sm text-tertiary-400">No upcoming rehearsals or sessions linked to this event yet.</p>
                    </div>
                </div>
            </div>

            <!-- Right: roster + checklist -->
            <div class="flex flex-col gap-6">
                <div v-if="canManage" id="roster-print-area" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-neutral-100">
                        <div class="flex items-center gap-1.5">
                            <CheckBadgeIcon class="w-4 h-4 text-primary-500" />
                            <h2 class="font-heading font-bold text-tertiary-900">Roster RSVPs</h2>
                        </div>
                        <p class="text-xs text-tertiary-400 mt-0.5">Privileged view — visible to managers only.</p>
                    </div>
                    <div class="divide-y divide-neutral-100 max-h-[420px] overflow-y-auto">
                        <div v-for="member in event.roster" :key="member.member_id" class="px-5 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 overflow-hidden" :class="member.avatar_path ? '' : colorForId(member.user_id)">
                                        <img v-if="member.avatar_path" :src="`/storage/${member.avatar_path}`" class="w-full h-full object-cover" :alt="member.name" />
                                        <template v-else>{{ getInitials(member.name) }}</template>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-tertiary-800 truncate">{{ member.name }}</p>
                                        <p class="text-xs text-tertiary-400 truncate">
                                            <span v-if="member.section">{{ member.section }} &middot; </span>{{ member.membership_number ?? member.email }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0 print:hidden">
                                    <span v-if="member.status" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700': member.status === 'going',
                                            'bg-secondary-100 text-secondary-700': member.status === 'maybe',
                                            'bg-tertiary-100 text-tertiary-500': member.status === 'not_going',
                                        }">
                                        {{ rsvpStatusLabels[member.status] }}
                                    </span>
                                    <template v-else>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-700">No Response</span>
                                    </template>
                                    <button v-if="member.status !== 'going' && member.status !== 'not_going'" type="button" @click="nudge(member)"
                                        title="Send a reminder" class="text-tertiary-300 hover:text-primary-600 transition-colors">
                                        <BellAlertIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p v-if="!event.roster.length" class="px-5 py-8 text-center text-sm text-tertiary-400">No active members.</p>
                    </div>
                </div>

                <div class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft overflow-hidden print:hidden">
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-neutral-100">
                        <h2 class="font-heading font-bold text-tertiary-900">Venue Approvals & Tech Clearances</h2>
                        <button v-if="canManage" type="button" @click="showChecklistForm = !showChecklistForm" class="text-tertiary-400 hover:text-primary-600 transition-colors">
                            <PlusIcon class="w-4 h-4" />
                        </button>
                    </div>

                    <form v-if="showChecklistForm" @submit.prevent="addChecklistItem" class="px-5 py-3 border-b border-neutral-100 flex items-center gap-2">
                        <input v-model="checklistForm.label" type="text" placeholder="e.g. Fire Marshal sign-off" required
                            class="flex-1 text-sm rounded-md border-neutral-200 focus:border-primary-500 focus:ring-primary-500" />
                        <select v-model="checklistForm.status" class="text-xs rounded-md border-neutral-200 focus:border-primary-500 focus:ring-primary-500">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="cleared">Cleared</option>
                        </select>
                        <button type="submit" :disabled="checklistForm.processing" class="text-xs font-semibold text-primary-600 hover:text-primary-700 shrink-0">Add</button>
                        <button type="button" @click="showChecklistForm = false" class="text-tertiary-300 hover:text-red-500 shrink-0">
                            <XMarkIcon class="w-4 h-4" />
                        </button>
                    </form>

                    <div class="divide-y divide-neutral-100">
                        <div v-for="item in event.checklist_items" :key="item.id" class="px-5 py-3 flex items-center justify-between gap-3">
                            <p class="text-sm text-tertiary-700 min-w-0 truncate">{{ item.label }}</p>
                            <div class="flex items-center gap-2 shrink-0">
                                <select v-if="canManage" :value="item.status" @change="setChecklistStatus(item, $event.target.value)"
                                    class="text-[11px] rounded-md border-neutral-200 focus:border-primary-500 focus:ring-primary-500 py-1">
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="cleared">Cleared</option>
                                </select>
                                <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide" :class="checklistStatusMeta[item.status].classes">
                                    {{ checklistStatusMeta[item.status].label }}
                                </span>
                                <button v-if="canManage" type="button" @click="removeChecklistItem(item)" class="text-tertiary-300 hover:text-red-500">
                                    <XMarkIcon class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                        <p v-if="!event.checklist_items.length" class="px-5 py-8 text-center text-sm text-tertiary-400">No checklist items yet.</p>
                    </div>
                </div>
            </div>
        </div>

        <NewEventModal v-if="canManage" :show="showEditModal" :organization-id="organization.id" :editing="event"
            :can-manage-attendance="canManageAttendance" :attendance-module-enabled="attendanceModuleEnabled"
            @close="showEditModal = false" />
    </AuthenticatedLayout>
</template>

<style>
@media print {
    body * { visibility: hidden; }
    #roster-print-area, #roster-print-area * { visibility: visible; }
    #roster-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>
