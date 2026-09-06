<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;
const organization = usePage().props.auth.organization; // Now pulling the organization from Inertia
</script>

<template>
    <!-- Background Gradient for base -->
    <div class="flex h-screen bg-gradient-to-br from-slate-900 via-indigo-900 to-slate-800 font-sans text-gray-100">

        <!-- Sidebar -->
        <aside class="w-64 bg-black/20 backdrop-blur-xl border-r border-white/10 flex flex-col hidden md:flex shadow-2xl">
            <!-- Dynamically display the organization name here -->
            <div class="h-16 flex items-center px-6 border-b border-white/10 font-bold text-lg tracking-wider text-white truncate">
                {{ organization ? organization.name : 'Platform' }} 
                <span class="text-indigo-300 text-xs ml-2 font-medium">Workspace</span>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <Link :href="route('dashboard')" class="block px-4 py-2 bg-white/10 rounded-lg text-sm font-medium text-white border border-white/5 shadow-inner">Dashboard</Link>
                <Link href="#" class="block px-4 py-2 text-indigo-100 hover:bg-white/5 hover:text-white transition-all rounded-lg text-sm font-medium">Members</Link>
                <Link href="#" class="block px-4 py-2 text-indigo-100 hover:bg-white/5 hover:text-white transition-all rounded-lg text-sm font-medium">Groups & Roles</Link>
                <Link href="#" class="block px-4 py-2 text-indigo-100 hover:bg-white/5 hover:text-white transition-all rounded-lg text-sm font-medium">Events & Calendar</Link>
                <Link href="#" class="block px-4 py-2 text-indigo-100 hover:bg-white/5 hover:text-white transition-all rounded-lg text-sm font-medium">Attendance</Link>
                <Link href="#" class="block px-4 py-2 text-indigo-100 hover:bg-white/5 hover:text-white transition-all rounded-lg text-sm font-medium">Files</Link>
            </nav>

            <div class="p-4 border-t border-white/10 space-y-1">
                 <Link :href="route('profile.edit')" class="block px-4 py-2 text-sm text-indigo-200 hover:bg-white/5 rounded-lg transition-all">Settings</Link>
                 
                 <!-- Inertia requires Log Out to be a POST request -->
                 <Link :href="route('logout')" method="post" as="button" class="w-full text-left block px-4 py-2 text-sm text-red-300 hover:bg-red-500/20 rounded-lg transition-all">
                     Log Out
                 </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden relative">
            <!-- Decorative background blur orbs -->
            <div class="absolute top-0 left-0 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl -z-10 pointer-events-none"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl -z-10 pointer-events-none"></div>

            <!-- Topbar -->
            <header class="h-16 bg-black/10 backdrop-blur-md border-b border-white/10 flex items-center justify-between px-6 z-10">
                <h1 class="text-xl font-semibold text-white drop-shadow-sm">
                    <slot name="header" />
                </h1>
                <div class="flex items-center space-x-4">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 text-white flex items-center justify-center text-sm font-bold shadow-lg border border-white/20">
                        {{ user.name.charAt(0) }}
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 z-10">
                <slot />
            </main>
        </div>
    </div>
</template>