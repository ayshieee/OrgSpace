<script setup>
import { computed } from 'vue';
import { MapPinIcon, EnvelopeIcon, PhoneIcon, ChatBubbleOvalLeftIcon, CheckBadgeIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    user: { type: Object, required: true },
});

const rows = computed(() => [
    { key: 'location', icon: MapPinIcon, label: 'Location', value: props.user.location },
    { key: 'email', icon: EnvelopeIcon, label: 'Email', value: props.user.email },
    { key: 'phone', icon: PhoneIcon, label: 'Phone', value: props.user.phone_number },
    { key: 'pronouns', icon: ChatBubbleOvalLeftIcon, label: 'Pronouns', value: props.user.pronouns },
].filter((row) => row.value));
</script>

<template>
    <section>
        <h2 class="font-heading text-lg font-bold text-tertiary-900">Personal Details</h2>

        <div v-if="rows.length" class="mt-4 space-y-4">
            <div v-for="row in rows" :key="row.key" class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0">
                    <component :is="row.icon" class="w-4 h-4" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-tertiary-400">{{ row.label }}</p>
                    <p class="text-sm text-tertiary-800 flex items-center flex-wrap gap-x-1.5 gap-y-1">
                        <span class="break-all">{{ row.value }}</span>
                        <span v-if="row.key === 'email' && !user.email_verified_at"
                            class="inline-flex items-center gap-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-600 bg-amber-50 border border-amber-200 rounded px-1.5 py-0.5 shrink-0">
                            Unverified
                        </span>
                        <CheckBadgeIcon v-else-if="row.key === 'email'" class="w-4 h-4 text-emerald-500 shrink-0" title="Verified" />
                    </p>
                </div>
            </div>
        </div>
        <p v-else class="mt-4 text-sm text-tertiary-400">No personal details added yet.</p>
    </section>
</template>
