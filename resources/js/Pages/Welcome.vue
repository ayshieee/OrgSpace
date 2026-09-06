<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="The Operating System for Your Organization" />

    <div class="min-h-screen bg-white font-sans text-gray-900 selection:bg-indigo-500 selection:text-white">
        
        <!-- Navigation -->
        <nav class="border-b border-gray-100 bg-white/80 backdrop-blur-md fixed w-full z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <div class="flex items-center gap-2">
                        <!-- Generic Logo (Jira uses blue geometric shapes) -->
                        <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center">
                            <div class="w-4 h-4 border-2 border-white rounded-sm"></div>
                        </div>
                        <span class="font-bold text-xl tracking-tight text-gray-900">OrgFlow</span>
                    </div>

                    <div class="hidden md:flex space-x-8">
                        <a href="#features" class="text-sm font-medium text-gray-600 hover:text-blue-600">Features</a>
                        <a href="#pricing" class="text-sm font-medium text-gray-600 hover:text-blue-600">Pricing</a>
                        <a href="#solutions" class="text-sm font-medium text-gray-600 hover:text-blue-600">Solutions</a>
                    </div>

                    <div class="flex items-center space-x-4">
                        <Link v-if="canLogin" :href="route('login')" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                            Log in
                        </Link>
                        <Link v-if="canRegister" :href="route('register')" class="bg-blue-600 text-white px-4 py-2 rounded-sm text-sm font-medium hover:bg-blue-700 transition-colors shadow-sm">
                            Try it free
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main class="pt-32 pb-16 sm:pt-40 sm:pb-24 lg:pb-32 overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                <div class="text-center max-w-3xl mx-auto">
                    <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl md:text-6xl">
                        Move work forward, <br>
                        <span class="text-blue-600">together.</span>
                    </h1>
                    <p class="mt-6 text-lg text-gray-500 leading-relaxed">
                       Ditch the messy group chats and scattered spreadsheets. Centralize your officers, members, attendance, and files in one highly customizable workspace built specifically for student organizations.
                    </p>
                    <div class="mt-8 flex justify-center gap-4">
                        <Link :href="route('register')" class="bg-blue-600 text-white px-8 py-3 rounded-sm text-base font-medium hover:bg-blue-700 shadow-md transition-all">
                            Get it free
                        </Link>
                        <a href="#features" class="bg-white text-gray-700 border border-gray-300 px-8 py-3 rounded-sm text-base font-medium hover:bg-gray-50 transition-all">
                            Explore features
                        </a>
                    </div>
                </div>

                <!-- Abstract UI Mockup Image -->
                <div class="mt-16 relative max-w-5xl mx-auto">
                    <div class="rounded-xl shadow-2xl border border-gray-200 bg-gray-50 overflow-hidden">
                        <div class="h-8 bg-gray-100 border-b border-gray-200 flex items-center px-4 gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                        </div>
                        <div class="p-8 grid grid-cols-4 gap-6 opacity-75">
                            <div class="col-span-1 space-y-4">
                                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                                <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                            </div>
                            <div class="col-span-3 space-y-4">
                                <div class="h-32 bg-blue-100 rounded border border-blue-200"></div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="h-24 bg-gray-200 rounded"></div>
                                    <div class="h-24 bg-gray-200 rounded"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <!-- Trusted By / Social Proof -->
        <section class="border-y border-gray-100 bg-white py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-center text-sm font-semibold uppercase tracking-widest text-gray-400 mb-8">
                    Trusted by student councils, varsity teams, and academic societies
                </p>
                <div class="flex justify-center gap-10 md:gap-16 flex-wrap items-center opacity-50 grayscale hover:grayscale-0 transition-all duration-500">
                    
                    <!-- Placeholder Logo 1: Tech Society -->
                    <div class="flex items-center gap-2 font-bold text-xl text-gray-900 tracking-tight">
                        <div class="w-6 h-6 bg-gray-900 rounded-sm"></div> Nu Laguna
                    </div>
                    
                    <!-- Placeholder Logo 2: University Varsity -->
                    <div class="flex items-center gap-2 font-serif font-bold text-xl text-gray-900">
                        <div class="w-6 h-6 rounded-full border-4 border-gray-900"></div> Honey University
                    </div>
                    
                    <!-- Placeholder Logo 3: Arts Guild -->
                    <div class="flex items-center gap-2 font-black text-xl text-gray-900 tracking-tighter">
                        <div class="w-0 h-0 border-l-[12px] border-l-transparent border-b-[20px] border-b-gray-900 border-r-[12px] border-r-transparent"></div> ARTS GUILD
                    </div>
                    
                    <!-- Placeholder Logo 4: Greek Life / Fraternity -->
                    <div class="flex items-center gap-2 font-medium text-xl text-gray-900">
                        <div class="w-6 h-6 bg-gray-900 rounded-tl-lg rounded-br-lg"></div> Alpha Beta
                    </div>
                    
                    <!-- Placeholder Logo 5: Debate Club (Hidden on very small screens) -->
                    <div class="hidden sm:flex items-center gap-2 font-bold text-xl text-gray-900 italic">
                        <div class="w-6 h-6 border-2 border-gray-900 rotate-45"></div> DEBATE TEAM
                    </div>

                </div>
            </div>
        </section>

        <!-- Deep Feature Showcase (Zig-Zag Layout) -->
        <section class="py-24 bg-white overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-24">
                
                <!-- Feature 1: Member Management (Text Left, Image Right) -->
                <div class="flex flex-col md:flex-row items-center gap-12 lg:gap-16">
                    <div class="flex-1 space-y-6">
                        <div class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-100">
                            Access Control
                        </div>
                        <h2 class="text-3xl font-bold text-gray-900 tracking-tight sm:text-4xl">
                            Dynamic roles and permissions
                        </h2>
                        <p class="text-lg text-gray-500 leading-relaxed">
                            Stop wrestling with rigid spreadsheets. Create custom permission tiers based entirely on what your organization needs, and control exactly who can view files, manage events, or update member records within your workspace.
                        </p>
                        <ul class="space-y-3 mt-4">
                            <li class="flex items-center text-gray-600">
                                <svg class="w-5 h-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Granular permission toggles
                            </li>
                            <li class="flex items-center text-gray-600">
                                <svg class="w-5 h-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Group-based assignments
                            </li>
                        </ul>
                    </div>
                    <!-- Abstract UI Mockup: Table -->
                    <div class="flex-1 w-full">
                        <div class="bg-gray-50 rounded-xl border border-gray-200 shadow-lg p-6">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center border-b border-gray-200 pb-4">
                                    <div class="h-4 bg-gray-300 rounded w-1/4"></div>
                                    <div class="h-8 bg-blue-600 rounded w-24"></div>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gray-300"></div>
                                        <div class="h-4 bg-gray-200 rounded w-32"></div>
                                    </div>
                                    <div class="h-6 bg-blue-100 text-blue-600 border border-blue-200 rounded-full w-20"></div>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gray-300"></div>
                                        <div class="h-4 bg-gray-200 rounded w-24"></div>
                                    </div>
                                    <div class="h-6 bg-gray-100 text-gray-600 border border-gray-200 rounded-full w-16"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 2: Attendance (Image Left, Text Right) -->
                <div class="flex flex-col md:flex-row-reverse items-center gap-12 lg:gap-16">
                    <div class="flex-1 space-y-6">
                        <div class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium text-purple-600 bg-purple-50 border border-purple-100">
                            Analytics
                        </div>
                        <h2 class="text-3xl font-bold text-gray-900 tracking-tight sm:text-4xl">
                            Track attendance in real-time
                        </h2>
                        <p class="text-lg text-gray-500 leading-relaxed">
                            Generate QR codes for meetings, track late arrivals, and view comprehensive dashboards that show you exactly who is showing up. Export your data instantly for organizational reporting.
                        </p>
                        <Link :href="route('register')" class="inline-flex items-center text-blue-600 font-semibold hover:text-blue-700 transition-colors">
                            Explore analytics 
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </Link>
                    </div>
                    <!-- Abstract UI Mockup: Charts -->
                    <div class="flex-1 w-full">
                        <div class="bg-gray-50 rounded-xl border border-gray-200 shadow-lg p-6">
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="h-24 bg-white border border-gray-200 rounded-lg p-4 flex flex-col justify-end">
                                    <div class="h-3 bg-green-400 rounded w-1/2 mb-2"></div>
                                    <div class="h-2 bg-gray-200 rounded w-3/4"></div>
                                </div>
                                <div class="h-24 bg-white border border-gray-200 rounded-lg p-4 flex flex-col justify-end">
                                    <div class="h-3 bg-amber-400 rounded w-1/3 mb-2"></div>
                                    <div class="h-2 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                            <div class="h-32 bg-white border border-gray-200 rounded-lg flex items-end p-4 gap-2">
                                <div class="w-1/6 bg-blue-200 h-1/2 rounded-t-sm"></div>
                                <div class="w-1/6 bg-blue-300 h-3/4 rounded-t-sm"></div>
                                <div class="w-1/6 bg-blue-400 h-full rounded-t-sm"></div>
                                <div class="w-1/6 bg-blue-500 h-5/6 rounded-t-sm"></div>
                                <div class="w-1/6 bg-blue-400 h-3/4 rounded-t-sm"></div>
                                <div class="w-1/6 bg-blue-200 h-1/3 rounded-t-sm"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature 3: Modular Workspace (Text Left, Image Right) -->
                <div class="flex flex-col md:flex-row items-center gap-12 lg:gap-16">
                    <div class="flex-1 space-y-6">
                        <div class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium text-emerald-600 bg-emerald-50 border border-emerald-100">
                            Modular Architecture
                        </div>
                        <h2 class="text-3xl font-bold text-gray-900 tracking-tight sm:text-4xl">
                            Turn on only what you need
                        </h2>
                        <p class="text-lg text-gray-500 leading-relaxed">
                            No two organizations operate the same way. Keep your workspace clutter-free by enabling only the modules your team actually uses. Turn on Finance for your treasurer, or Task Management for your committees, with a single click.
                        </p>
                        <div class="grid grid-cols-2 gap-4 mt-6">
                            <div class="flex items-center text-sm font-medium text-gray-900">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></div> Finance Tracking
                            </div>
                            <div class="flex items-center text-sm font-medium text-gray-900">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></div> Recruitment Pipeline
                            </div>
                            <div class="flex items-center text-sm font-medium text-gray-900">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></div> Equipment Inventory
                            </div>
                            <div class="flex items-center text-sm font-medium text-gray-900">
                                <div class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></div> Task Boards
                            </div>
                        </div>
                    </div>
                    <!-- Abstract UI Mockup: Toggle Switches -->
                    <div class="flex-1 w-full">
                        <div class="bg-gray-50 rounded-xl border border-gray-200 shadow-lg p-6 space-y-4">
                            <!-- Toggle 1 (Active) -->
                            <div class="bg-white border border-gray-200 rounded-lg p-4 flex justify-between items-center shadow-sm">
                                <div class="space-y-1">
                                    <div class="h-4 bg-gray-800 rounded w-24"></div>
                                    <div class="h-3 bg-gray-400 rounded w-48"></div>
                                </div>
                                <div class="w-10 h-6 bg-emerald-500 rounded-full flex items-center p-1 justify-end">
                                    <div class="w-4 h-4 bg-white rounded-full"></div>
                                </div>
                            </div>
                            <!-- Toggle 2 (Inactive) -->
                            <div class="bg-white border border-gray-200 rounded-lg p-4 flex justify-between items-center shadow-sm opacity-60">
                                <div class="space-y-1">
                                    <div class="h-4 bg-gray-800 rounded w-32"></div>
                                    <div class="h-3 bg-gray-400 rounded w-40"></div>
                                </div>
                                <div class="w-10 h-6 bg-gray-300 rounded-full flex items-center p-1">
                                    <div class="w-4 h-4 bg-white rounded-full"></div>
                                </div>
                            </div>
                            <!-- Toggle 3 (Active) -->
                            <div class="bg-white border border-gray-200 rounded-lg p-4 flex justify-between items-center shadow-sm">
                                <div class="space-y-1">
                                    <div class="h-4 bg-gray-800 rounded w-20"></div>
                                    <div class="h-3 bg-gray-400 rounded w-52"></div>
                                </div>
                                <div class="w-10 h-6 bg-emerald-500 rounded-full flex items-center p-1 justify-end">
                                    <div class="w-4 h-4 bg-white rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- Call to Action -->
        <section class="py-20 bg-blue-600 text-white">
            <div class="max-w-4xl mx-auto text-center px-4">
                <h2 class="text-3xl font-bold mb-6">Built for scale. Free for small teams.</h2>
                <p class="text-blue-100 mb-8 text-lg">
                    Start today with our fully-featured Free plan. No credit card required. Upgrade to Premium only when your organization demands advanced automation and unlimited capacity.
                </p>
                <Link :href="route('register')" class="bg-white text-blue-600 px-8 py-3 rounded-sm text-base font-bold hover:bg-gray-10 shadow-lg transition-all">
                    Create your workspace
                </Link>
            </div>
        </section>

        <!-- Simple Footer -->
        <footer class="bg-white border-t border-gray-200 py-12 text-center text-sm text-gray-500">
            <p>&copy; 2026 OrgFlow Platform. All rights reserved.</p>
        </footer>

    </div>
</template>