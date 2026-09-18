<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    HomeIcon,
    UsersIcon,
    QrCodeIcon,
    MegaphoneIcon,
    FolderIcon,
    CalendarDaysIcon,
    MusicalNoteIcon,
    Cog6ToothIcon,
    MagnifyingGlassIcon,
    BellIcon,
    ChevronLeftIcon,
} from '@heroicons/vue/24/outline';
import { orgTypeIcon } from '@/utils/orgTypeIcon';
import FlashMessages from '@/Components/FlashMessages.vue';
import UserMenu from '@/Components/UserMenu.vue';

const page = usePage();
const organization = computed(() => page.props.auth.organization);
const permissions = computed(() => page.props.auth.permissions ?? []);
const enabledModules = computed(() => page.props.auth.enabledModules ?? []);
const unreadNotifications = computed(() => page.props.auth.unread_notifications_count ?? 0);

const canManageSettings = computed(() => permissions.value.includes('manage_org_settings'));

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
        { key: 'music_library', label: 'Music Library', icon: MusicalNoteIcon, href: route('organizations.music.index', orgId) },
    ];

    return items
        .filter((item) => item.always || item.key === 'member_management' || enabledModules.value.includes(item.key))
        .map((item) => ({ ...item, available: Boolean(item.href) }));
});

const isCurrent = (href) => href && page.url.startsWith(new URL(href, window.location.origin).pathname);
</script>

<template>
    <div class="flex h-screen bg-neutral-50 font-sans text-tertiary-800 animate-fade-in-up">
        <!-- Sidebar -->
        <aside class="w-64 bg-gradient-to-b from-primary-500 via-primary to-primary-800 border-r border-primary-800 flex-col hidden md:flex shrink-0">
            <!-- Back to Spaces -->
            <div class="px-5 pt-5 pb-3 shrink-0">
                <Link :href="route('dashboard')"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-primary-200 hover:text-white transition-colors duration-200">
                    <ChevronLeftIcon class="w-3.5 h-3.5" /> Spaces
                </Link>
            </div>

            <!-- Organization identity -->
            <div v-if="organization" class="px-5 pb-4 border-b border-primary-500/30 flex items-center gap-3 shrink-0">
                <div class="w-14 h-14 rounded-2xl overflow-hidden bg-white/15 ring-1 ring-white/20 flex items-center justify-center shrink-0">
                    <img v-if="organization.logo_path" :src="`/storage/${organization.logo_path}`" class="w-full h-full object-cover" :alt="organization.name" />
                    <component v-else :is="orgTypeIcon(organization.type)" class="w-7 h-7 text-white" />
                </div>
                <p class="font-heading font-bold text-white leading-tight break-words">{{ organization.name }}</p>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <template v-for="item in navItems" :key="item.key">
                    <Link
                        v-if="item.available"
                        :href="item.href"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 ease-ios border-l-[3px]"
                        :class="isCurrent(item.href) ? 'bg-white text-primary-700 font-semibold border-secondary-400 shadow-elevated' : 'text-primary-100 border-transparent hover:bg-white/10 hover:text-white hover:translate-x-0.5'"
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
                <Link :href="route('logout')" method="post" as="button" class="w-full text-left flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-red-300 hover:bg-white/10 hover:text-red-200 rounded-lg transition-colors">
                    Log Out
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Topbar -->
            <header class="h-16 bg-white/90 backdrop-blur-xl border-b border-neutral-200 flex items-center justify-between px-6 shrink-0 shadow-soft">
                <div>
                    <p class="text-xs text-tertiary-400 font-medium">OrgSpace / <span class="text-tertiary-600"><slot name="header" /></span></p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative hidden lg:block">
                        <MagnifyingGlassIcon class="w-4 h-4 text-tertiary-300 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input type="text" disabled placeholder="Search workspace… (coming soon)"
                            class="w-64 pl-9 pr-3 py-2 text-sm rounded-lg border-neutral-200 bg-neutral-50 text-tertiary-400 placeholder:text-tertiary-300 cursor-not-allowed" />
                    </div>

                    <Link :href="route('notifications.index')" title="Notifications" class="relative p-2 rounded-lg text-tertiary-400 hover:bg-neutral-100 transition-colors duration-200">
                        <BellIcon class="w-5 h-5" />
                        <span v-if="unreadNotifications > 0"
                            class="absolute top-1 right-1 min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">
                            {{ unreadNotifications > 9 ? '9+' : unreadNotifications }}
                        </span>
                    </Link>

                    <UserMenu />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 bg-neutral-50">
                <FlashMessages />
                <slot />
            </main>
        </div>
    </div>
</template>
