<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

// Tell Vue to expect the 'stats' object from our backend route
defineProps({
    stats: Object
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            Overview
        </template>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Stat Cards -->
            <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-[0_8px_32px_0_rgba(0,0,0,0.3)]">
                <h3 class="text-sm font-medium text-indigo-200 mb-1">Total Members</h3>
                <!-- Dynamically display total members -->
                <p class="text-3xl font-bold text-white">{{ stats.total_members }}</p>
            </div>
            <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-[0_8px_32px_0_rgba(0,0,0,0.3)]">
                <h3 class="text-sm font-medium text-indigo-200 mb-1">Upcoming Events</h3>
                <!-- Dynamically display upcoming events -->
                <p class="text-3xl font-bold text-white">{{ stats.upcoming_events }}</p>
            </div>
            <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 shadow-[0_8px_32px_0_rgba(0,0,0,0.3)]">
                <h3 class="text-sm font-medium text-indigo-200 mb-1">Avg. Attendance</h3>
                <!-- Dynamically display average attendance -->
                <p class="text-3xl font-bold text-emerald-400">{{ stats.avg_attendance }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Announcements -->
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl flex flex-col shadow-[0_8px_32px_0_rgba(0,0,0,0.3)]">
                <div class="px-6 py-4 border-b border-white/10 bg-white/5 rounded-t-2xl">
                    <h2 class="font-semibold text-white">Recent Announcements</h2>
                </div>
                <div class="p-6 text-sm text-indigo-100 flex-1">
                    <div class="mb-4">
                        <strong class="text-white block mb-1">General Rehearsal moved</strong>
                        <span class="opacity-80">The Friday rehearsal has been moved to Room 402.</span>
                    </div>
                    <div class="w-full h-px bg-white/10 my-4"></div>
                    <div>
                        <strong class="text-white block mb-1">New Sheet Music Uploaded</strong>
                        <span class="opacity-80">Check the Soprano folder for the latest revisions.</span>
                    </div>
                </div>
            </div>

            <!-- Events -->
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl flex flex-col shadow-[0_8px_32px_0_rgba(0,0,0,0.3)]">
                <div class="px-6 py-4 border-b border-white/10 bg-white/5 rounded-t-2xl">
                    <h2 class="font-semibold text-white">Next 7 Days</h2>
                </div>
                <div class="p-6 text-sm text-indigo-100 flex-1 space-y-4">
                    <div class="flex justify-between items-center">
                        <span>Sectionals (Tenor/Bass)</span>
                        <span class="px-3 py-1 bg-indigo-500/30 border border-indigo-400/30 text-indigo-200 rounded-full text-xs font-medium backdrop-blur-sm">Tomorrow, 5:00 PM</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Full Choir Rehearsal</span>
                        <span class="px-3 py-1 bg-white/10 border border-white/20 text-white rounded-full text-xs font-medium backdrop-blur-sm">Friday, 6:00 PM</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>