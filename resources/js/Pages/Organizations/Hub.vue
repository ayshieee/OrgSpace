<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    Cog6ToothIcon,
    StarIcon,
    ClockIcon,
    EyeSlashIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { orgTypeIcon } from '@/utils/orgTypeIcon';
import UserMenu from '@/Components/UserMenu.vue';

defineProps({
    manage: { type: Array, required: true },
    memberOf: { type: Array, required: true },
    pendingRequests: { type: Array, required: true },
    hidden: { type: Array, required: true },
});

const hiddenExpanded = ref(true);
</script>

<template>
    <Head title="My Organizations" />

    <div class="min-h-screen bg-neutral-50 font-sans flex flex-col">
        <!-- Top bar -->
        <header class="bg-white border-b border-neutral-200">
            <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
                <span class="font-heading font-extrabold text-xl text-primary">OrgSpace</span>

                <div class="flex items-center gap-4">
                    <Link :href="route('get-started.show')" class="inline-flex items-center rounded-lg border border-transparent bg-gradient-to-b from-secondary-400 to-secondary-500 px-4 py-2 text-sm font-semibold text-tertiary-900 shadow-soft transition-all duration-200 ease-ios hover:shadow-elevated hover:from-secondary-300 hover:to-secondary-400 active:scale-[0.98]">
                        Join or Create Organization
                    </Link>
                    <UserMenu />
                </div>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-6 py-10 flex-1 w-full">
            <h1 class="font-heading text-2xl font-extrabold text-tertiary-900 tracking-tight">My Organizations</h1>
            <p class="mt-1 text-sm text-tertiary-500">Manage your active memberships and administrative roles.</p>

            <!-- Manage -->
            <section v-if="manage.length" class="mt-10">
                <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-tertiary-500 mb-3">
                    <Cog6ToothIcon class="w-4 h-4 text-primary-500" /> Manage
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link v-for="org in manage" :key="org.id" :href="route('organizations.dashboard', org.id)"
                        class="block bg-white rounded-xl border border-neutral-200 border-l-4 border-l-primary p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 overflow-hidden">
                                    <img v-if="org.logo_path" :src="`/storage/${org.logo_path}`" class="w-full h-full object-cover" :alt="org.name" />
                                    <component v-else :is="orgTypeIcon(org.type)" class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="font-heading font-bold text-tertiary-900">{{ org.name }}</p>
                                    <p class="text-xs text-tertiary-500">{{ org.role ?? 'Owner' }}</p>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-primary-100 text-primary-700 shrink-0">Admin</span>
                        </div>
                        <div class="mt-3 pt-3 border-t border-neutral-100 text-xs text-tertiary-400 flex items-center gap-1">
                            <UsersIcon class="w-4 h-4" /> {{ org.members_count }} Members
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Member of -->
            <section v-if="memberOf.length" class="mt-10">
                <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-tertiary-500 mb-3">
                    <StarIcon class="w-4 h-4 text-secondary-500" /> Member of
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link v-for="org in memberOf" :key="org.id" :href="route('organizations.dashboard', org.id)"
                        class="block bg-white rounded-xl border border-neutral-200 p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-neutral-100 text-tertiary-500 flex items-center justify-center shrink-0 overflow-hidden">
                                <img v-if="org.logo_path" :src="`/storage/${org.logo_path}`" class="w-full h-full object-cover" :alt="org.name" />
                                <component v-else :is="orgTypeIcon(org.type)" class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="font-heading font-bold text-tertiary-900">{{ org.name }}</p>
                                <p class="text-xs text-tertiary-500">Active Member</p>
                            </div>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Pending Requests -->
            <section v-if="pendingRequests.length" class="mt-10">
                <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-tertiary-500 mb-3">
                    <ClockIcon class="w-4 h-4 text-secondary-500" /> Pending Requests
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="org in pendingRequests" :key="org.id" class="bg-white rounded-xl border border-neutral-200 p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-neutral-100 text-tertiary-500 flex items-center justify-center shrink-0 overflow-hidden">
                                    <img v-if="org.logo_path" :src="`/storage/${org.logo_path}`" class="w-full h-full object-cover" :alt="org.name" />
                                    <component v-else :is="orgTypeIcon(org.type)" class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="font-heading font-bold text-tertiary-900">{{ org.name }}</p>
                                    <p class="text-xs text-tertiary-500">Awaiting Approval</p>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-secondary-100 text-secondary-700 shrink-0">Pending</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Hidden / Archived -->
            <section v-if="hidden.length" class="mt-10">
                <button type="button" @click="hiddenExpanded = !hiddenExpanded" class="w-full flex items-center justify-between text-sm font-bold uppercase tracking-wide text-tertiary-500 mb-3">
                    <span class="flex items-center gap-2">
                        <EyeSlashIcon class="w-4 h-4 text-tertiary-400" /> Hidden / Archived
                    </span>
                    <component :is="hiddenExpanded ? ChevronUpIcon : ChevronDownIcon" class="w-4 h-4" />
                </button>
                <div v-if="hiddenExpanded" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="org in hidden" :key="org.id" class="bg-neutral-100 rounded-xl border border-neutral-200 p-4 opacity-70">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-neutral-200 text-tertiary-400 flex items-center justify-center shrink-0 overflow-hidden">
                                    <img v-if="org.logo_path" :src="`/storage/${org.logo_path}`" class="w-full h-full object-cover" :alt="org.name" />
                                    <component v-else :is="orgTypeIcon(org.type)" class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="font-heading font-bold text-tertiary-600">{{ org.name }}</p>
                                    <p class="text-xs text-tertiary-400">{{ org.note }}</p>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-neutral-200 text-tertiary-500 shrink-0">Archived</span>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-neutral-200 py-6 mt-10">
            <div class="max-w-6xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-tertiary-400">
                <span>&copy; {{ new Date().getFullYear() }} OrgSpace Student Leadership Platform. All rights reserved.</span>
                <div class="flex gap-4">
                    <a href="#" class="hover:text-tertiary-600">Support</a>
                    <a href="#" class="hover:text-tertiary-600">Privacy Policy</a>
                    <a href="#" class="hover:text-tertiary-600">Terms of Service</a>
                </div>
            </div>
        </footer>
    </div>
</template>
