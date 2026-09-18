<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { UsersIcon } from '@heroicons/vue/24/outline';
import { getInitials, colorForId } from '@/utils/initials';

const props = defineProps({
    organization: { type: Object, required: true },
});

const requesting = ref(false);

function requestToJoin() {
    requesting.value = true;
    router.post(route('join.request', props.organization.id), {}, {
        preserveScroll: true,
        onFinish: () => (requesting.value = false),
    });
}
</script>

<template>
    <div class="group relative border border-neutral-200 rounded-xl p-4 overflow-hidden">
        <div v-if="organization.logo_path" class="w-10 h-10 rounded-lg overflow-hidden border border-neutral-200">
            <img :src="`/storage/${organization.logo_path}`" class="w-full h-full object-cover" :alt="organization.name" />
        </div>
        <div v-else class="w-10 h-10 rounded-lg flex items-center justify-center font-bold text-sm" :class="colorForId(organization.id)">
            {{ getInitials(organization.name) }}
        </div>

        <h3 class="font-heading font-bold text-tertiary-900 text-sm mt-2.5 truncate">{{ organization.name }}</h3>

        <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold"
            :class="organization.already_requested ? 'bg-secondary-100 text-secondary-700' : 'bg-neutral-100 text-tertiary-500'">
            {{ organization.already_requested ? 'Requested' : 'Public' }}
        </span>

        <p v-if="organization.description" class="text-xs text-tertiary-500 mt-2 line-clamp-1">{{ organization.description }}</p>

        <div class="flex items-center gap-1 text-xs text-tertiary-400 mt-2">
            <UsersIcon class="w-3.5 h-3.5" /> {{ organization.members_count }}
        </div>

        <!-- Hover-reveal action -->
        <div v-if="!organization.already_requested"
            class="absolute inset-0 flex items-center justify-center bg-white/95 opacity-0 group-hover:opacity-100 transition-opacity">
            <button type="button" @click="requestToJoin" :disabled="requesting"
                class="inline-flex items-center rounded-lg border border-transparent bg-gradient-to-b from-secondary-400 to-secondary-500 px-4 py-2 text-xs font-semibold text-tertiary-900 shadow-soft transition-all duration-200 ease-ios hover:shadow-elevated hover:from-secondary-300 hover:to-secondary-400 active:scale-[0.98] disabled:opacity-50">
                {{ requesting ? 'Sending…' : 'Request to Join' }}
            </button>
        </div>
    </div>
</template>
