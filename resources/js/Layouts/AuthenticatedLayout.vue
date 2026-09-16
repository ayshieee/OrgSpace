<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    HomeIcon,
    UsersIcon,
    QrCodeIcon,
    MegaphoneIcon,
    FolderIcon,
    CalendarDaysIcon,
    Cog6ToothIcon,
    MagnifyingGlassIcon,
    BellIcon,
    ChevronDownIcon,
    Squares2X2Icon,
    ClipboardDocumentCheckIcon,
} from '@heroicons/vue/24/outline';
import { getInitials, colorForId } from '@/utils/initials';

const vClickOutside = {
    mounted(el, binding) {
        el.__clickOutsideHandler__ = (event) => {
            if (!el.contains(event.target)) {
                binding.value(event);
            }
        };
        document.addEventListener('mousedown', el.__clickOutsideHandler__);
    },
    unmounted(el) {
        document.removeEventListener('mousedown', el.__clickOutsideHandler__);
    },
};

const page = usePage();
const user = computed(() => page.props.auth.user);
const organization = computed(() => page.props.auth.organization);
const permissions = computed(() => page.props.auth.permissions ?? []);
const enabledModules = computed(() => page.props.auth.enabledModules ?? []);

const canManageSettings = computed(() => permissions.value.includes('manage_org_settings'));
const canReviewRequests = computed(() => permissions.value.includes('manage_roster'));
const showQuickActions = computed(() => canManageSettings.value || canReviewRequests.value);

const navItems = computed(() => {
    if (!organization.value) return [];

    const orgId = organization.value.id;
    const items = [
        { key: 'dashboard', label: 'Dashboard', icon: HomeIcon, href: route('organizations.dashboard', orgId), always: true },
        { key: 'member_management', label: 'Members', icon: UsersIcon, href: route('organizations.members.index', orgId) },
        { key: 'attendance', label: 'Attendance', icon: QrCodeIcon, href: route('organizations.attendance.index', orgId) },
        { key: 'announcements', label: 'Announcements', icon: MegaphoneIcon, href: route('organizations.announcements.index', orgId) },
        { key: 'files', label: 'Files', icon: FolderIcon, href: route('organizations.files.index', orgId) },
        { key: 'events', label: 'Events', icon: CalendarDaysIcon, href: route('organizations.events.index', orgId) },
    ];

    return items
        .filter((item) => item.always || item.key === 'member_management' || enabledModules.value.includes(item.key))
        .map((item) => ({ ...item, available: Boolean(item.href) }));
});

const isCurrent = (href) => href && page.url.startsWith(new URL(href, window.location.origin).pathname);

const quickActionsOpen = ref(false);
const userMenuOpen = ref(false);
</script>

<template>
    <div class="flex h-screen bg-neutral-50 font-sans text-tertiary-800">
        <!-- Sidebar -->
        <aside class="w-64 bg-primary border-r border-primary-800 flex-col hidden md:flex shrink-0">
            <div class="h-16 px-5 flex items-center border-b border-primary-500/30 shrink-0">
                <Link :href="route('dashboard')" class="font-heading font-extrabold text-2xl tracking-tight text-white">OrgSpace</Link>
            </div>

            <div v-if="organization" class="px-5 py-4 border-b border-primary-500/30">
                <Link :href="route('dashboard')" class="text-xs text-primary-200 hover:text-white font-medium">&larr; My Organizations</Link>
                <div class="font-heading font-bold text-white truncate mt-1">{{ organization.name }}</div>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <template v-for="item in navItems" :key="item.key">
                    <Link
                        v-if="item.available"
                        :href="item.href"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors border-l-2"
                        :class="isCurrent(item.href) ? 'bg-white/12 text-white border-secondary-400' : 'text-primary-100 border-transparent hover:bg-white/10 hover:text-white'"
                    >
                        <component :is="item.icon" class="w-4 h-4" />
                        {{ item.label }}
                    </Link>
                    <div v-else class="flex items-center justify-between gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-primary-300 cursor-not-allowed">
                        <span class="flex items-center gap-2.5">
                            <component :is="item.icon" class="w-4 h-4" />
                            {{ item.label }}
                        </span>
                        <span class="text-[10px] font-bold uppercase tracking-wide bg-primary-800 text-primary-200 px-1.5 py-0.5 rounded">Soon</span>
                    </div>
                </template>
            </nav>

            <div class="p-3 border-t border-primary-500/30 space-y-1">
                <Link v-if="canManageSettings" :href="route('organization.settings.show')"
                    class="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-primary-100 hover:bg-white/10 hover:text-white rounded-lg transition-colors">
                    <Cog6ToothIcon class="w-4 h-4" /> Organization Settings
                </Link>
                <Link :href="route('profile.edit')" class="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-primary-100 hover:bg-white/10 hover:text-white rounded-lg transition-colors">
                    Account Settings
                </Link>
                <Link :href="route('logout')" method="post" as="button" class="w-full text-left flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-red-300 hover:bg-white/10 hover:text-red-200 rounded-lg transition-colors">
                    Log Out
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Topbar -->
            <header class="h-16 bg-white border-b border-neutral-200 flex items-center justify-between px-6 shrink-0">
                <div>
                    <p class="text-xs text-tertiary-400 font-medium">OrgSpace / <span class="text-tertiary-600"><slot name="header" /></span></p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative hidden lg:block">
                        <MagnifyingGlassIcon class="w-4 h-4 text-tertiary-300 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input type="text" disabled placeholder="Search workspace… (coming soon)"
                            class="w-64 pl-9 pr-3 py-2 text-sm rounded-md border-neutral-200 bg-neutral-50 text-tertiary-400 placeholder:text-tertiary-300 cursor-not-allowed" />
                    </div>

                    <button type="button" title="Notifications (coming soon)" class="p-2 rounded-md text-tertiary-400 hover:bg-neutral-100">
                        <BellIcon class="w-5 h-5" />
                    </button>

                    <div v-if="showQuickActions" class="relative" v-click-outside="() => (quickActionsOpen = false)">
                        <button type="button" @click="quickActionsOpen = !quickActionsOpen"
                            class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3.5 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-primary-700">
                            <Squares2X2Icon class="w-4 h-4" /> Quick Actions <ChevronDownIcon class="w-3.5 h-3.5" />
                        </button>
                        <div v-if="quickActionsOpen"
                            class="absolute right-0 mt-2 w-56 bg-white border border-neutral-200 rounded-lg shadow-lg py-1.5 z-20">
                            <Link v-if="canReviewRequests && organization" :href="`${route('organizations.dashboard', organization.id)}#pending-approvals`"
                                class="flex items-center gap-2 px-3.5 py-2 text-sm text-tertiary-700 hover:bg-neutral-50" @click="quickActionsOpen = false">
                                <ClipboardDocumentCheckIcon class="w-4 h-4 text-tertiary-400" /> Review Join Requests
                            </Link>
                            <Link v-if="canManageSettings" :href="route('organization.settings.show')"
                                class="flex items-center gap-2 px-3.5 py-2 text-sm text-tertiary-700 hover:bg-neutral-50" @click="quickActionsOpen = false">
                                <Cog6ToothIcon class="w-4 h-4 text-tertiary-400" /> Organization Settings
                            </Link>
                        </div>
                    </div>

                    <div class="relative" v-click-outside="() => (userMenuOpen = false)">
                        <button type="button" @click="userMenuOpen = !userMenuOpen" class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold shrink-0" :class="colorForId(user.id)">
                            {{ getInitials(user.name) }}
                        </button>
                        <div v-if="userMenuOpen"
                            class="absolute right-0 mt-2 w-48 bg-white border border-neutral-200 rounded-lg shadow-lg py-1.5 z-20">
                            <Link :href="route('profile.edit')" class="block px-3.5 py-2 text-sm text-tertiary-700 hover:bg-neutral-50">Account Settings</Link>
                            <Link :href="route('logout')" method="post" as="button" class="w-full text-left block px-3.5 py-2 text-sm text-red-500 hover:bg-red-50">Log Out</Link>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 bg-neutral-50">
                <slot />
            </main>
        </div>
    </div>
</template>
