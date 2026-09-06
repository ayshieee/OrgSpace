<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    organization_name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Create your workspace" />

    <!-- Main Container -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans text-gray-900 relative overflow-hidden">
        
        <!-- Spotlight Center Glow (New) -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-blue-400/20 rounded-full blur-[100px] z-0 pointer-events-none"></div>

        <!-- Background Dot Grid -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjEiIGZpbGw9InJnYmEoMCwwLDAsMC4wNSkiLz48L3N2Zz4=')] z-0 opacity-60"></div>

        <!-- Floating Isometric Element (Top Right) -->
        <div class="absolute top-0 right-0 lg:top-10 lg:right-20 w-72 h-72 z-0 pointer-events-none hidden md:block" style="perspective: 1000px;">
            <div class="w-full h-full relative" style="transform: rotateX(55deg) rotateZ(-45deg); transform-style: preserve-3d;">
                <!-- Base -->
                <div class="absolute inset-0 bg-blue-500/20 backdrop-blur-md border-2 border-blue-400/50 rounded-3xl shadow-[0_20px_40px_rgba(59,130,246,0.3)]" style="transform: translateZ(0px);"></div>
                <!-- Middle Layer -->
                <div class="absolute inset-6 bg-indigo-500/30 backdrop-blur-lg border-2 border-indigo-400/60 rounded-2xl shadow-xl" style="transform: translateZ(40px);"></div>
                <!-- Top Block -->
                <div class="absolute top-12 left-12 w-24 h-24 bg-blue-600/50 backdrop-blur-xl border-2 border-blue-400/80 rounded-xl shadow-2xl" style="transform: translateZ(80px);"></div>
            </div>
        </div>

        <!-- Floating Isometric Element (Bottom Left) -->
        <div class="absolute -bottom-10 -left-10 lg:bottom-10 lg:left-10 w-96 h-96 z-0 pointer-events-none hidden md:block" style="perspective: 1200px;">
            <div class="w-full h-full relative animate-pulse" style="transform: rotateX(55deg) rotateZ(-45deg); transform-style: preserve-3d; animation-duration: 8s;">
                <!-- Base -->
                <div class="absolute inset-0 bg-emerald-500/20 backdrop-blur-md border-2 border-emerald-400/50 rounded-[40px] shadow-[0_20px_50px_rgba(16,185,129,0.2)]" style="transform: translateZ(0px);"></div>
                <!-- Middle Layer (Dashboard Representation) -->
                <div class="absolute inset-8 bg-white/60 backdrop-blur-lg border-2 border-white/80 rounded-2xl shadow-2xl flex flex-col gap-4 p-6" style="transform: translateZ(60px);">
                    <div class="w-3/4 h-4 bg-gray-400/40 rounded-full"></div>
                    <div class="w-1/2 h-4 bg-gray-400/40 rounded-full"></div>
                </div>
                <!-- Floating Graph Element -->
                <div class="absolute bottom-12 right-12 w-16 h-24 bg-emerald-500/60 backdrop-blur-xl border-2 border-emerald-400/80 rounded-lg shadow-2xl" style="transform: translateZ(100px);"></div>
            </div>
        </div>

        <!-- Floating Mini Accent (Top Left) (New) -->
        <div class="absolute top-20 left-32 w-8 h-8 rounded-full bg-gradient-to-br from-amber-300 to-orange-400 shadow-lg blur-[2px] opacity-80 animate-bounce" style="animation-duration: 4s;"></div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center relative z-10">
            <!-- Logo -->
            <div class="flex justify-center items-center gap-2 mb-6">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded flex items-center justify-center shadow-lg border border-white/20">
                    <div class="w-5 h-5 border-2 border-white rounded-sm"></div>
                </div>
                <span class="font-bold text-2xl tracking-tight text-gray-900 drop-shadow-sm">OrgFlow</span>
            </div>
            
            <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                Create your free workspace
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Already have an account?
                <Link :href="route('login')" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
                    Log in here
                </Link>
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md relative z-10">
            <!-- Heavy Glassmorphism Card -->
            <div class="bg-white/70 backdrop-blur-xl py-8 px-4 shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-white/60 sm:rounded-3xl sm:px-10">
                <form @submit.prevent="submit" class="space-y-5">
                    
                    <!-- Organization Name -->
                    <div>
                        <label for="organization_name" class="block text-sm font-medium text-gray-700">Organization Name</label>
                        <div class="relative mt-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <!-- Building Icon -->
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            </div>
                            <input id="organization_name" v-model="form.organization_name" type="text" required autofocus
                                class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-300/80 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors bg-white/90" 
                                placeholder="e.g. Student Council" />
                        </div>
                        <div v-if="form.errors.organization_name" class="mt-1 text-sm text-red-600">{{ form.errors.organization_name }}</div>
                    </div>

                    <!-- User Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Your Full Name</label>
                        <div class="relative mt-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <!-- User Icon -->
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <input id="name" v-model="form.name" type="text" required
                                class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-300/80 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors bg-white/90" />
                        </div>
                        <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Work or School Email</label>
                        <div class="relative mt-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <!-- Mail Icon -->
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </div>
                            <input id="email" v-model="form.email" type="email" required
                                class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-300/80 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors bg-white/90" />
                        </div>
                        <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="relative mt-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <!-- Lock Icon -->
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </div>
                            <input id="password" v-model="form.password" type="password" required
                                class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-300/80 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors bg-white/90" />
                        </div>
                        <div v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</div>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <div class="relative mt-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <!-- Lock Icon -->
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </div>
                            <input id="password_confirmation" v-model="form.password_confirmation" type="password" required
                                class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-300/80 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors bg-white/90" />
                        </div>
                        <div v-if="form.errors.password_confirmation" class="mt-1 text-sm text-red-600">{{ form.errors.password_confirmation }}</div>
                    </div>

                    <!-- Submit Button (Upgraded) -->
                    <div class="pt-4">
                        <button type="submit" :disabled="form.processing"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-[0_4px_14px_0_rgba(99,102,241,0.39)] text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-all active:scale-[0.98]">
                            {{ form.processing ? 'Creating workspace...' : 'Create workspace' }}
                        </button>
                    </div>
                    
                    <!-- Terms -->
                    <p class="text-xs text-center text-gray-500 mt-4">
                        By creating a workspace, you agree to our <a href="#" class="text-indigo-600 font-medium hover:underline">Terms of Service</a> and <a href="#" class="text-indigo-600 font-medium hover:underline">Privacy Policy</a>.
                    </p>
                </form>
            </div>
        </div>
    </div>
</template>