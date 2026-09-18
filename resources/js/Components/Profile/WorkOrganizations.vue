<script setup>
import { ref, computed } from 'vue';
import { BriefcaseIcon, BuildingOffice2Icon } from '@heroicons/vue/24/outline';

const props = defineProps({
    memberships: { type: Array, required: true },
});

const expanded = ref(false);
const LIMIT = 3;

const visible = computed(() => expanded.value ? props.memberships : props.memberships.slice(0, LIMIT));
</script>

<template>
    <section>
        <h2 class="flex items-center gap-2 font-heading text-lg font-bold text-tertiary-900">
            <BriefcaseIcon class="w-5 h-5 text-primary-500" /> Work / Organizations
        </h2>

        <div v-if="memberships.length" class="mt-4 divide-y divide-neutral-100">
            <div v-for="m in visible" :key="m.id" class="py-4 first:pt-0 flex items-start gap-3">
                <div class="w-11 h-11 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 overflow-hidden">
                    <img v-if="m.logo_path" :src="`/storage/${m.logo_path}`" class="w-full h-full object-cover" :alt="m.organization_name" />
                    <BuildingOffice2Icon v-else class="w-5 h-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-tertiary-900 text-sm">{{ m.organization_name }}</p>
                    <p class="text-sm text-tertiary-500">{{ m.role }} &middot; Active Member</p>
                    <p class="text-xs text-tertiary-400 mt-0.5">
                        Joined {{ m.joined_at }}
                        <span v-if="m.student_id"> &middot; Student ID: {{ m.student_id }}</span>
                    </p>
                </div>
            </div>
        </div>
        <p v-else class="mt-4 text-sm text-tertiary-400">You're not a member of any organization yet.</p>

        <button v-if="memberships.length > LIMIT" type="button" @click="expanded = !expanded"
            class="mt-3 text-sm font-semibold text-primary-600 hover:text-primary-700 transition-colors">
            {{ expanded ? 'Show less' : `Show all (${memberships.length})` }}
        </button>
    </section>
</template>
