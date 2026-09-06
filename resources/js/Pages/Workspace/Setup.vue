<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { DotLottieVue } from '@lottiefiles/dotlottie-vue'; // Imported the Lottie Vue player

const props = defineProps({
    organization: Object
});

const currentStep = ref(0);
const loadingText = ref('Initializing secure environment...');

const form = useForm({
    modules: ['members'], // 'members' is the required core module
    invites: ['', '', ''] 
});

const availableModules = [
    { 
        id: 'attendance', 
        name: 'Attendance Tracking', 
        desc: 'Scan QR codes for fast meeting attendance.',
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' 
    },
    { 
        id: 'files', 
        name: 'Secure Files', 
        desc: 'Cloud storage for organization documents.',
        icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z' 
    },
    { 
        id: 'finance', 
        name: 'Finance & Dues', 
        desc: 'Track organization budgets and member fees.',
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' 
    },
    { 
        id: 'tasks', 
        name: 'Task Boards', 
        desc: 'Visual Kanban boards for event planning.',
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' 
    },
    { 
        id: 'calendar', 
        name: 'Events Calendar', 
        desc: 'Schedule and manage upcoming meetings.',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' 
    }
];

// Computed properties for the dynamic progress bar
const totalModules = computed(() => availableModules.length + 1);
const enabledModules = computed(() => form.modules.length);
const progressPercentage = computed(() => (enabledModules.value / totalModules.value) * 100);

// Extended 10-Second Theatrical Loading Sequence
onMounted(() => {
    // Spread the status messages out over the 10 seconds
    setTimeout(() => { loadingText.value = 'Provisioning tenant database...'; }, 2500);
    setTimeout(() => { loadingText.value = 'Applying organizational constraints...'; }, 5000);
    setTimeout(() => { loadingText.value = 'Finalizing workspace instance...'; }, 7500);

    // Transition to the actual setup step exactly at 10 seconds
    setTimeout(() => {
        currentStep.value = 1;
    }, 10000);
});

const nextStep = () => { currentStep.value = 2; };
const prevStep = () => { currentStep.value = 1; };

const skipAndSubmit = () => {
    form.invites = ['', '', ''];
    form.post(route('setup.store'));
};

const submit = () => {
    form.post(route('setup.store'));
};
</script>

<template>
    <Head title="Setting up Workspace..." />

    <!-- Unified Brand Background -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 flex flex-col items-center justify-center p-4 sm:p-6 lg:p-8 font-sans selection:bg-blue-200 relative overflow-hidden">
        
        <!-- Spotlight Center Glow -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-blue-400/20 rounded-full blur-[100px] z-0 pointer-events-none"></div>

        <!-- Background Dot Grid -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjEiIGZpbGw9InJnYmEoMCwwLDAsMC4wNSkiLz48L3N2Zz4=')] z-0 opacity-60"></div>

        <!-- Floating Isometric Element (Top Right) -->
        <div class="absolute top-0 right-0 lg:top-10 lg:right-20 w-72 h-72 z-0 pointer-events-none hidden md:block" style="perspective: 1000px;">
            <div class="w-full h-full relative" style="transform: rotateX(55deg) rotateZ(-45deg); transform-style: preserve-3d;">
                <div class="absolute inset-0 bg-blue-500/20 backdrop-blur-md border-2 border-blue-400/50 rounded-3xl shadow-[0_20px_40px_rgba(59,130,246,0.3)]" style="transform: translateZ(0px);"></div>
                <div class="absolute inset-6 bg-indigo-500/30 backdrop-blur-lg border-2 border-indigo-400/60 rounded-2xl shadow-xl" style="transform: translateZ(40px);"></div>
                <div class="absolute top-12 left-12 w-24 h-24 bg-blue-600/50 backdrop-blur-xl border-2 border-blue-400/80 rounded-xl shadow-2xl" style="transform: translateZ(80px);"></div>
            </div>
        </div>

        <!-- Floating Isometric Element (Bottom Left) -->
        <div class="absolute -bottom-10 -left-10 lg:bottom-10 lg:left-10 w-96 h-96 z-0 pointer-events-none hidden md:block" style="perspective: 1200px;">
            <div class="w-full h-full relative animate-pulse" style="transform: rotateX(55deg) rotateZ(-45deg); transform-style: preserve-3d; animation-duration: 8s;">
                <div class="absolute inset-0 bg-emerald-500/20 backdrop-blur-md border-2 border-emerald-400/50 rounded-[40px] shadow-[0_20px_50px_rgba(16,185,129,0.2)]" style="transform: translateZ(0px);"></div>
                <div class="absolute inset-8 bg-white/60 backdrop-blur-lg border-2 border-white/80 rounded-2xl shadow-2xl flex flex-col gap-4 p-6" style="transform: translateZ(60px);">
                    <div class="w-3/4 h-4 bg-gray-400/40 rounded-full"></div>
                    <div class="w-1/2 h-4 bg-gray-400/40 rounded-full"></div>
                </div>
                <div class="absolute bottom-12 right-12 w-16 h-24 bg-emerald-500/60 backdrop-blur-xl border-2 border-emerald-400/80 rounded-lg shadow-2xl" style="transform: translateZ(100px);"></div>
            </div>
        </div>

        <!-- MAIN MODAL CONTAINER -->
        <div class="w-full max-w-xl mx-auto bg-white/70 backdrop-blur-xl rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-white/80 overflow-hidden relative z-10 transition-all duration-500">
            
            <transition name="fade" mode="out-in">
                
                <!-- STEP 0: UPGRADED THEATRICAL LOADING SCREEN (LOTTIE) -->
                <div v-if="currentStep === 0" key="step0" class="flex flex-col items-center justify-center p-12 sm:p-20 min-h-[420px]">
                    
                   <!-- Lottie Animation -->
<div class="w-72 h-72 sm:w-96 sm:h-96 -mt-12 mb-2 flex items-center justify-center">
    <DotLottieVue 
        autoplay 
        loop 
        src="https://lottie.host/0ea14969-5bb5-40cb-bc57-af9da6fccf2e/z3P7mK47sJ.lottie" 
        class="w-full h-full scale-125"
    />
</div>

                    <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight mb-8">Building {{ organization?.name || 'Workspace' }}</h2>
                    
                    <!-- Filling Progress Bar -->
                    <div class="w-full max-w-xs h-1.5 bg-gray-200/60 rounded-full overflow-hidden mb-4 shadow-inner">
                        <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full load-bar-fill"></div>
                    </div>

                    <!-- Rapidly Changing Status Text -->
                    <div class="h-6 flex items-center justify-center overflow-hidden">
                        <transition name="slide-up" mode="out-in">
                            <p :key="loadingText" class="text-[13px] font-medium text-gray-500 tracking-wide uppercase">
                                {{ loadingText }}
                            </p>
                        </transition>
                    </div>
                </div>

                <!-- STEP 1: MODULE SELECTION -->
                <div v-else-if="currentStep === 1" key="step1" class="p-6 sm:p-10">
                    
                    <!-- Centered Header & Progress Bar -->
                    <div class="mb-8 text-center">
                        <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Build your workspace</h2>
                        <p class="mt-1.5 text-[14px] text-gray-600">Enable the modules <span class="font-bold text-gray-800">{{ organization?.name || 'your organization' }}</span> needs.</p>
                        
                        <div class="mt-5 max-w-xs mx-auto">
                            <p class="text-[12px] text-blue-600 font-semibold mb-2">{{ enabledModules }} of {{ totalModules }} enabled</p>
                            <div class="w-full h-1.5 bg-gray-200/50 rounded-full overflow-hidden shadow-inner">
                                <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full transition-all duration-500 ease-out" :style="{ width: `${progressPercentage}%` }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Single Column Toggle List -->
                    <div class="flex flex-col gap-3">
                        
                        <!-- Core Module (Locked) -->
                        <div class="flex items-center p-4 rounded-2xl border border-blue-300 bg-blue-50/70 shadow-sm cursor-not-allowed">
                            <div class="w-10 h-10 flex-shrink-0 rounded-xl bg-white border border-blue-100 flex items-center justify-center text-blue-600 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-[14px] font-bold text-gray-900">Member Directory</h3>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 text-blue-700">Required</span>
                                </div>
                                <p class="text-[12px] text-gray-500 mt-0.5">Core database of members and roles.</p>
                            </div>
                            <div class="flex-shrink-0 ml-3 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            </div>
                        </div>

                        <!-- Dynamic Selectable Modules -->
                        <label v-for="mod in availableModules" :key="mod.id" 
                               class="group flex items-center p-4 rounded-2xl border cursor-pointer transition-all duration-300"
                               :class="form.modules.includes(mod.id) ? 'border-blue-400 bg-white/90 shadow-sm ring-1 ring-blue-500/50' : 'border-white/80 bg-white/40 hover:bg-white/80 hover:border-gray-300 shadow-sm'">
                            
                            <input type="checkbox" v-model="form.modules" :value="mod.id" class="hidden">
                            
                            <div class="w-10 h-10 flex-shrink-0 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center transition-colors duration-300 shadow-sm"
                                 :class="form.modules.includes(mod.id) ? 'text-blue-600 border-blue-200 bg-blue-50' : 'text-gray-400 group-hover:text-gray-600'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mod.icon"></path></svg>
                            </div>
                            
                            <div class="ml-4 flex-1">
                                <h3 class="text-[14px] font-bold transition-colors" :class="form.modules.includes(mod.id) ? 'text-gray-900' : 'text-gray-700'">{{ mod.name }}</h3>
                                <p class="text-[12px] text-gray-500 mt-0.5">{{ mod.desc }}</p>
                            </div>

                            <!-- Modern Toggle Switch -->
                            <div class="flex-shrink-0 ml-3 relative w-11 h-6 rounded-full transition-colors duration-200 ease-in-out border border-transparent shadow-inner"
                                 :class="form.modules.includes(mod.id) ? 'bg-blue-600' : 'bg-gray-200 group-hover:bg-gray-300'">
                                <div class="absolute top-[1px] left-[2px] w-5 h-5 bg-white rounded-full transition-transform duration-200 ease-in-out shadow-sm border border-gray-100"
                                     :class="form.modules.includes(mod.id) ? 'translate-x-[22px]' : 'translate-x-0'"></div>
                            </div>
                        </label>
                    </div>

                    <!-- Clean Footer -->
                    <div class="mt-8 pt-5 border-t border-gray-200/50 flex items-center justify-between">
                        <span class="text-[12px] text-gray-500 font-medium hidden sm:block">Settings can be adjusted later.</span>
                        <button type="button" @click="nextStep"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-2.5 rounded-xl text-[14px] font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md transition-all active:scale-[0.98]">
                            Continue
                        </button>
                    </div>
                </div>

                <!-- STEP 2: INVITE TEAM -->
                <div v-else key="step2" class="p-8 sm:p-10">
                    
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg border border-white/50 mb-5 text-white">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Invite your team</h2>
                        <p class="mt-1.5 text-[14px] text-gray-600">Send invites to your co-officers to start collaborating immediately.</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div v-for="(invite, index) in form.invites" :key="index">
                            <input v-model="form.invites[index]" type="email" placeholder="e.g. member@school.edu"
                                class="block w-full px-4 py-3.5 border border-white bg-white/60 rounded-xl text-[14px] focus:ring-blue-600 focus:border-blue-600 focus:bg-white outline-none transition-all shadow-sm" />
                        </div>

                        <!-- Step 2 Footer Action -->
                        <div class="mt-10 pt-6 border-t border-gray-200/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <button type="button" @click="prevStep" class="text-[14px] font-semibold text-gray-500 hover:text-gray-900 transition-colors">
                                ← Back
                            </button>
                            
                            <div class="flex items-center gap-4">
                                <button type="button" @click="skipAndSubmit" class="text-[14px] font-semibold text-gray-500 hover:text-gray-900 transition-colors">
                                    Skip for now
                                </button>
                                <button type="submit" :disabled="form.processing"
                                    class="inline-flex items-center justify-center px-6 py-3 rounded-xl text-[14px] font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md transition-all active:scale-[0.98] disabled:opacity-50">
                                    {{ form.processing ? 'Launching...' : 'Launch Workspace' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </transition>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.fade-enter-from {
  opacity: 0;
  transform: scale(0.98) translateY(10px);
}
.fade-leave-to {
  opacity: 0;
  transform: scale(0.98) translateY(-10px);
}

.load-bar-fill {
    width: 0%;
    /* Updated to 10 seconds */
    animation: loadBar 10s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes loadBar {
    0% { width: 0%; }
    20% { width: 35%; }
    60% { width: 65%; }
    80% { width: 90%; }
    100% { width: 100%; }
}

/* Text transition for loading states */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.2s ease;
}
.slide-up-enter-from {
  opacity: 0;
  transform: translateY(10px);
}
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>