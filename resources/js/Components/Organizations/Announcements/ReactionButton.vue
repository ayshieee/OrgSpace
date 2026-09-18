<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { HeartIcon as HeartOutlineIcon } from '@heroicons/vue/24/outline';
import { HeartIcon as HeartSolidIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    organizationId: { type: String, required: true },
    announcementId: { type: String, required: true },
    count: { type: Number, required: true },
    reactedByMe: { type: Boolean, required: true },
});

// Optimistic local copies so the click feels instant; reset from props
// whenever a fresh server payload lands (e.g. after the Inertia reload).
const localReacted = ref(props.reactedByMe);
const localCount = ref(props.count);
const popping = ref(false);
const pending = ref(false);

watch(() => [props.reactedByMe, props.count], ([reacted, count]) => {
    localReacted.value = reacted;
    localCount.value = count;
});

function toggle() {
    if (pending.value) return;
    pending.value = true;

    const wasReacted = localReacted.value;
    localReacted.value = !wasReacted;
    localCount.value += wasReacted ? -1 : 1;
    popping.value = true;
    setTimeout(() => (popping.value = false), 350);

    router.post(route('organizations.announcements.reaction.toggle', [props.organizationId, props.announcementId]), {}, {
        preserveScroll: true,
        preserveState: true,
        only: ['announcements'],
        onError: () => {
            localReacted.value = wasReacted;
            localCount.value = props.count;
        },
        onFinish: () => (pending.value = false),
    });
}
</script>

<template>
    <button type="button" @click="toggle"
        class="inline-flex items-center gap-1.5 text-xs font-semibold transition-colors duration-200 shrink-0"
        :class="localReacted ? 'text-red-500' : 'text-tertiary-400 hover:text-red-400'">
        <HeartSolidIcon v-if="localReacted" class="w-4 h-4" :class="popping ? 'animate-heart-pop' : ''" />
        <HeartOutlineIcon v-else class="w-4 h-4" :class="popping ? 'animate-heart-pop' : ''" />
        <span>{{ localCount }}</span>
    </button>
</template>
