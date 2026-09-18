<script setup>
import { Head } from '@inertiajs/vue3';
import { CheckCircleIcon, ClockIcon, LockClosedIcon, NoSymbolIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    status: { type: String, required: true }, // checked_in | already_checked_in | closed | expired | not_started | not_a_member
    sessionTitle: { type: String, required: true },
    organizationName: { type: String, required: true },
    checkedInAt: { type: String, default: null },
});

const copy = {
    checked_in: { icon: CheckCircleIcon, tone: 'text-emerald-500', title: "You're checked in!" },
    already_checked_in: { icon: ClockIcon, tone: 'text-primary-500', title: 'Already checked in' },
    closed: { icon: LockClosedIcon, tone: 'text-tertiary-400', title: 'This session is closed' },
    expired: { icon: ExclamationTriangleIcon, tone: 'text-amber-500', title: 'This session has expired' },
    not_started: { icon: ClockIcon, tone: 'text-tertiary-400', title: "This session hasn't started yet" },
    not_a_member: { icon: NoSymbolIcon, tone: 'text-red-500', title: "You're not a member here" },
}[props.status];
</script>

<template>
    <Head title="Attendance Check-In" />

    <div class="min-h-screen bg-gradient-to-br from-neutral-50 via-primary-50 to-neutral-100 flex flex-col items-center justify-center px-4 font-sans">
        <div class="w-full max-w-sm bg-white rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.08)] border border-neutral-200 p-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-neutral-100 flex items-center justify-center mb-5">
                <component :is="copy.icon" class="w-7 h-7" :class="copy.tone" />
            </div>
            <h1 class="font-heading text-xl font-extrabold text-tertiary-900">{{ copy.title }}</h1>

            <p class="mt-2 text-sm text-tertiary-500" v-if="status === 'checked_in'">
                Marked present for <span class="text-tertiary-800 font-semibold">{{ sessionTitle }}</span> at {{ checkedInAt }}.
            </p>
            <p class="mt-2 text-sm text-tertiary-500" v-else-if="status === 'already_checked_in'">
                You were already marked present for <span class="text-tertiary-800 font-semibold">{{ sessionTitle }}</span> at {{ checkedInAt }}.
            </p>
            <p class="mt-2 text-sm text-tertiary-500" v-else-if="status === 'closed'">
                <span class="text-tertiary-800 font-semibold">{{ sessionTitle }}</span> has already been closed by an officer. If this is a mistake, ask them to reopen it.
            </p>
            <p class="mt-2 text-sm text-tertiary-500" v-else-if="status === 'expired'">
                <span class="text-tertiary-800 font-semibold">{{ sessionTitle }}</span>'s check-in window has ended. Ask an officer if you still need to be marked present.
            </p>
            <p class="mt-2 text-sm text-tertiary-500" v-else-if="status === 'not_started'">
                <span class="text-tertiary-800 font-semibold">{{ sessionTitle }}</span> hasn't opened for check-in yet. Try again once it starts.
            </p>
            <p class="mt-2 text-sm text-tertiary-500" v-else>
                This QR code is for <span class="text-tertiary-800 font-semibold">{{ organizationName }}</span>, and you're not currently an active member there.
            </p>
        </div>

        <span class="mt-6 font-heading font-extrabold text-lg text-primary">OrgSpace</span>
    </div>
</template>
