<script setup>
import { ref, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ArrowLeftIcon, MagnifyingGlassIcon, TicketIcon } from '@heroicons/vue/24/outline';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import OrganizationCard from '@/Components/OrganizationCard.vue';

const props = defineProps({
    organizations: { type: Array, required: true },
    query: { type: String, default: '' },
});

const codeForm = useForm({ code: '' });
function joinWithCode() {
    codeForm.post(route('join.code'));
}

const search = ref(props.query);
let debounceTimer = null;

watch(search, (value) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(route('join.index'), { q: value }, { preserveState: true, replace: true });
    }, 300);
});
</script>

<template>
    <Head title="Join an Organization" />

    <div class="min-h-screen bg-neutral-50 font-sans">
        <header class="relative bg-white border-b border-neutral-200 h-16 flex items-center justify-center">
            <Link :href="route('get-started.show')" class="absolute left-6 text-tertiary-500 hover:text-tertiary-700">
                <ArrowLeftIcon class="w-5 h-5" />
            </Link>
            <span class="font-heading font-extrabold text-xl text-primary">OrgSpace</span>
        </header>

        <main class="max-w-5xl mx-auto px-6 py-10">
            <h1 class="font-heading text-2xl font-extrabold text-tertiary-900 tracking-tight">Join an Organization</h1>
            <p class="mt-1.5 text-sm text-tertiary-500">Have a code from your organization? Enter it below. Otherwise, search for your organization.</p>

            <div class="max-w-sm mx-auto mt-10 border-l-4 border-secondary bg-secondary-50/40 rounded-r-lg p-4">
                <h3 class="flex items-center gap-1.5 text-sm font-bold text-tertiary-900 mb-2">
                    <TicketIcon class="w-4 h-4 text-secondary-600" /> Have a join code?
                </h3>
                <form @submit.prevent="joinWithCode" class="flex gap-2">
                    <TextInput v-model="codeForm.code" type="text" class="flex-1" placeholder="Enter join code" />
                    <PrimaryButton :disabled="codeForm.processing">Join</PrimaryButton>
                </form>
                <InputError :message="codeForm.errors.code" class="mt-1" />
                <p class="mt-1.5 text-xs text-tertiary-500">Codes are shared by your organization's officers and grant immediate access.</p>
            </div>

            <div class="relative text-center my-10">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-neutral-200"></div></div>
                <span class="relative bg-neutral-50 px-3 text-xs text-tertiary-400 uppercase tracking-wide">or browse public organizations</span>
            </div>

            <div class="relative mb-6">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <MagnifyingGlassIcon class="w-4 h-4 text-tertiary-300" />
                </div>
                <TextInput v-model="search" type="text" class="w-full pl-9" placeholder="Search organizations by name..." />
            </div>

            <div v-if="organizations.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <OrganizationCard v-for="org in organizations" :key="org.id" :organization="org" />
            </div>
            <p v-else class="text-center text-sm text-tertiary-400 py-8">No public organizations found.</p>
        </main>
    </div>
</template>
