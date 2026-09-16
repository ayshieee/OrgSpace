<script setup>
import { Head, Link } from '@inertiajs/vue3';
import heroIllustration from '../../images/hero-illustration.png';
import {
    UserPlusIcon,
    Cog6ToothIcon,
    UsersIcon,
    Squares2X2Icon,
    QrCodeIcon,
    MegaphoneIcon,
    FolderIcon,
    CalendarDaysIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const steps = [
    { icon: UserPlusIcon, title: 'Create your account', description: 'Sign up with your university email.' },
    { icon: Cog6ToothIcon, title: 'Set up organization', description: 'Choose roles and features to match your workflow.' },
    { icon: UsersIcon, title: 'Invite members', description: 'Bring your team onboard with instant invite links.' },
    { icon: Squares2X2Icon, title: 'Start managing', description: 'Centralize attendance, files, and events in one place.' },
];

const modules = [
    { icon: QrCodeIcon, color: 'primary', title: 'Attendance', description: 'Track presence seamlessly with QR codes or manual entry for any meeting.' },
    { icon: MegaphoneIcon, color: 'secondary', title: 'Announcements', description: 'Ensure important updates reach everyone with centralized, prioritized messaging.' },
    { icon: FolderIcon, color: 'primary', title: 'File Library', description: 'Store contributions, budgets, and historical documents in a secure, organized vault.' },
    { icon: CalendarDaysIcon, color: 'primary', title: 'Events & Calendar', description: 'Plan, schedule, and promote upcoming events with a unified organization calendar.' },
];
</script>

<template>
    <Head title="One Platform. Every Organization, Its Own Way." />

    <div class="min-h-screen bg-white font-sans text-tertiary-900 selection:bg-primary-500 selection:text-white">

        <!-- Navigation -->
        <nav class="border-b border-neutral-200 bg-white/80 backdrop-blur-md fixed w-full z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <div class="flex items-center">
                        <span class="font-heading font-extrabold text-xl tracking-tight text-primary">OrgSpace</span>
                    </div>

                    <div class="hidden md:flex space-x-8">
                        <a href="#features" class="text-sm font-medium text-tertiary-500 hover:text-primary-600">Features</a>
                        <a href="#how-it-works" class="text-sm font-medium text-tertiary-500 hover:text-primary-600">How it Works</a>
                        <a href="#pricing" class="text-sm font-medium text-tertiary-500 hover:text-primary-600">Pricing</a>
                        <a href="#contact" class="text-sm font-medium text-tertiary-500 hover:text-primary-600">Contact</a>
                    </div>

                    <div class="flex items-center space-x-4">
                        <Link v-if="canLogin" :href="route('login')" class="text-sm font-medium text-tertiary-500 hover:text-tertiary-900">
                            Log in
                        </Link>
                        <Link v-if="canRegister" :href="route('register')" class="bg-secondary text-tertiary-900 px-4 py-2 rounded-md text-sm font-semibold hover:bg-secondary-600 transition-colors shadow-sm">
                            Get Started
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="pt-32 pb-20 sm:pt-40 sm:pb-28 overflow-hidden bg-gradient-to-b from-neutral-50 to-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <h1 class="font-heading text-4xl font-extrabold tracking-tight text-tertiary-900 sm:text-5xl">
                            One platform. Every<br>organization, <span class="text-primary">its own way.</span>
                        </h1>
                        <p class="mt-6 text-lg text-tertiary-500 leading-relaxed max-w-xl">
                            Empower your organization with a modular management system tailored to your specific needs. Choose your features, define your view, and lead with confidence.
                        </p>
                        <div class="mt-8 flex flex-wrap gap-4">
                            <Link :href="route('register')" class="bg-tertiary text-white px-8 py-3 rounded-md text-base font-semibold hover:bg-tertiary-800 shadow-md transition-all">
                                Get Started
                            </Link>
                            <a href="#how-it-works" class="bg-white text-tertiary-700 border border-neutral-300 px-8 py-3 rounded-md text-base font-semibold hover:bg-neutral-50 transition-all">
                                See How it Works
                            </a>
                        </div>
                    </div>

                    <!-- Hero illustration -->
                    <div class="relative hidden lg:flex items-center justify-center">
                        <img :src="heroIllustration" alt="Modular organization dashboard illustration" class="w-full max-w-lg h-auto" />
                    </div>
                </div>
            </div>
        </section>

        <!-- How it Works -->
        <section id="how-it-works" class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="font-heading text-3xl font-bold text-tertiary-900 tracking-tight sm:text-4xl">How it Works</h2>
                    <p class="mt-3 text-lg text-tertiary-500">Get your organization up and running in minutes, not days.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
                    <div v-for="(step, index) in steps" :key="step.title" class="text-center">
                        <div class="relative inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-50 border border-primary-100 text-primary-600 mb-5">
                            <component :is="step.icon" class="w-7 h-7" />
                            <span class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center">{{ index + 1 }}</span>
                        </div>
                        <h3 class="font-heading font-bold text-tertiary-900">{{ step.title }}</h3>
                        <p class="mt-1.5 text-sm text-tertiary-500">{{ step.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Modular Tools -->
        <section id="features" class="py-24 bg-neutral-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="font-heading text-3xl font-bold text-tertiary-900 tracking-tight sm:text-4xl">Modular Tools for Any Org</h2>
                    <p class="mt-3 text-lg text-tertiary-500">Activate only the features you need. Keep your workspace clean and focused.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div v-for="mod in modules" :key="mod.title" class="bg-white rounded-2xl border border-neutral-200 p-6 flex gap-4 shadow-sm">
                        <div class="shrink-0 w-11 h-11 rounded-xl flex items-center justify-center"
                            :class="mod.color === 'primary' ? 'bg-primary-50 text-primary-600' : 'bg-secondary-50 text-secondary-600'">
                            <component :is="mod.icon" class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-heading font-bold text-tertiary-900">{{ mod.title }}</h3>
                            <p class="mt-1 text-sm text-tertiary-500">{{ mod.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer id="contact" class="bg-neutral-100 border-t border-neutral-200 pt-16 pb-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-center mb-12">
                    <span class="px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest text-tertiary-500 bg-white border border-neutral-200">Built for Organizations</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-10 pb-10 border-b border-neutral-200">
                    <div>
                        <span class="font-heading font-extrabold text-lg text-tertiary-900">OrgSpace</span>
                        <p class="mt-2 text-sm text-tertiary-500">&copy; {{ new Date().getFullYear() }} OrgSpace. Empowering Student Leadership.</p>
                    </div>
                    <div>
                        <h4 class="font-heading font-bold text-sm text-tertiary-900 mb-3">Legal</h4>
                        <ul class="space-y-2 text-sm text-tertiary-500">
                            <li><a href="#" class="hover:text-primary-600">Privacy Policy</a></li>
                            <li><a href="#" class="hover:text-primary-600">Terms of Service</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-heading font-bold text-sm text-tertiary-900 mb-3">Company</h4>
                        <ul class="space-y-2 text-sm text-tertiary-500">
                            <li><a href="#" class="hover:text-primary-600">Support</a></li>
                        </ul>
                    </div>
                </div>

                <p class="mt-8 text-center text-xs text-tertiary-400">&copy; {{ new Date().getFullYear() }} OrgSpace. All rights reserved.</p>
            </div>
        </footer>

    </div>
</template>
