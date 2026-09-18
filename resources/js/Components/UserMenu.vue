<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { getInitials, colorForId } from '@/utils/initials';
import { vClickOutside } from '@/directives/clickOutside';

const page = usePage();
const user = computed(() => page.props.auth.user);
const menuOpen = ref(false);
</script>

<template>
    <div class="relative" v-click-outside="() => (menuOpen = false)">
        <button type="button" @click="menuOpen = !menuOpen"
            class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold shrink-0 overflow-hidden transition-transform duration-200 ease-ios hover:scale-105 active:scale-95"
            :class="user.avatar_path ? '' : colorForId(user.id)">
            <img v-if="user.avatar_path" :src="`/storage/${user.avatar_path}`" class="w-full h-full object-cover" :alt="user.name" />
            <template v-else>{{ getInitials(user.name) }}</template>
        </button>
        <Transition
            enter-active-class="transition duration-150 ease-ios"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="menuOpen"
                class="absolute right-0 mt-2 w-56 bg-white border border-neutral-200/60 rounded-2xl shadow-elevated py-1.5 z-20 origin-top-right">
                <div class="px-3.5 py-2.5 flex items-center gap-2.5 border-b border-neutral-100">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold shrink-0 overflow-hidden"
                        :class="user.avatar_path ? '' : colorForId(user.id)">
                        <img v-if="user.avatar_path" :src="`/storage/${user.avatar_path}`" class="w-full h-full object-cover" :alt="user.name" />
                        <template v-else>{{ getInitials(user.name) }}</template>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-tertiary-900 truncate">{{ user.name }}</p>
                        <p class="text-xs text-tertiary-400 truncate">{{ user.email }}</p>
                    </div>
                </div>
                <Link :href="route('profile.edit')" class="block px-3.5 py-2 text-sm text-tertiary-700 hover:bg-neutral-50 transition-colors">Profile</Link>
                <Link :href="route('logout')" method="post" as="button" class="w-full text-left block px-3.5 py-2 text-sm text-red-500 hover:bg-red-50 transition-colors">Log Out</Link>
            </div>
        </Transition>
    </div>
</template>
