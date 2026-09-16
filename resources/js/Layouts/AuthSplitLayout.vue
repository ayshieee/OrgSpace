<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    active: { type: String, required: true }, // 'login' | 'signup'
});

const year = new Date().getFullYear();
</script>

<template>
    <div class="min-h-screen bg-neutral-100 flex items-center justify-center p-4 sm:p-8 font-sans">
        <div class="w-full max-w-4xl bg-white rounded-2xl shadow-xl border border-neutral-200 overflow-hidden flex">
            <!-- Left Panel -->
            <div class="hidden lg:flex lg:w-2/5 relative bg-primary text-white flex-col justify-between p-8">
                <div class="absolute -top-16 -right-16 w-56 h-56 rounded-full bg-primary-400/40 pointer-events-none"></div>

                <div class="relative z-10">
                    <span class="font-heading font-extrabold text-xl">OrgSpace</span>
                </div>

                <div class="relative z-10">
                    <h1 class="font-heading text-3xl font-extrabold leading-tight mb-6">
                        Manage your organization, your way.
                    </h1>
                    <ul class="space-y-3 text-sm text-primary-50">
                        <li class="flex items-center gap-2"><span>🎓</span> Built for NU Laguna's RSOs</li>
                        <li class="flex items-center gap-2"><span>✅</span> Set up your org in minutes</li>
                    </ul>
                </div>

                <p class="relative z-10 text-xs text-primary-100">&copy; {{ year }} OrgSpace. Empowering Student Leadership.</p>
            </div>

            <!-- Right Panel -->
            <div class="flex-1 p-6 sm:p-10">
                <div class="lg:hidden text-center mb-8">
                    <span class="font-heading font-extrabold text-xl text-primary">OrgSpace</span>
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-neutral-200 mb-8">
                    <Link :href="route('login')" class="flex-1 text-center pb-3 text-sm font-semibold -mb-px border-b-2 transition-colors"
                        :class="active === 'login' ? 'text-tertiary-900 border-secondary' : 'text-tertiary-400 border-transparent hover:text-tertiary-600'">
                        Log In
                    </Link>
                    <Link :href="route('register')" class="flex-1 text-center pb-3 text-sm font-semibold -mb-px border-b-2 transition-colors"
                        :class="active === 'signup' ? 'text-tertiary-900 border-secondary' : 'text-tertiary-400 border-transparent hover:text-tertiary-600'">
                        Sign Up
                    </Link>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
