<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import UserMenu from '@/Components/UserMenu.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
</script>

<template>
    <div class="min-h-screen bg-neutral-50 font-sans flex flex-col">
        <!-- Top bar -->
        <header class="bg-white border-b border-neutral-200">
            <div class="max-w-5xl mx-auto px-6 h-16 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="font-heading font-extrabold text-xl text-primary">OrgSpace</span>
                    <Link :href="route('dashboard')" class="hidden sm:inline-flex items-center gap-1 text-sm font-medium text-tertiary-400 hover:text-tertiary-700 transition-colors">
                        <ArrowLeftIcon class="w-3.5 h-3.5" /> Back to Spaces
                    </Link>
                </div>

                <UserMenu />
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-6 py-10 flex-1 w-full">
            <FlashMessages />
            <slot />
        </main>
    </div>
</template>
