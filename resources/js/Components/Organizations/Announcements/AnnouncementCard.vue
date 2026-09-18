<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    PencilSquareIcon, TrashIcon, MapPinIcon, BellAlertIcon, ChevronDownIcon,
    CheckCircleIcon, LinkIcon, DocumentIcon, ArrowTopRightOnSquareIcon, FlagIcon,
    ChatBubbleLeftIcon,
} from '@heroicons/vue/24/outline';
import AnnouncementBody from './AnnouncementBody.vue';
import ReactionButton from './ReactionButton.vue';
import CommentThread from './CommentThread.vue';

const props = defineProps({
    announcement: { type: Object, required: true },
    organizationId: { type: String, required: true },
    canManage: { type: Boolean, default: false },
});

const emit = defineEmits(['edit']);

const nudging = ref(false);
const receiptsOpen = ref(false);
const commentsOpen = ref(false);

// Shown as a compact corner preview next to the title instead of a full-width
// block — any further attachments still render in the grid below untouched.
const primaryAttachment = computed(() => props.announcement.attachments?.[0] ?? null);
const remainingAttachments = computed(() => props.announcement.attachments?.slice(1) ?? []);

function nudge() {
    nudging.value = true;
    router.post(route('organizations.announcements.nudge', [props.organizationId, props.announcement.id]), {}, {
        preserveScroll: true,
        onFinish: () => (nudging.value = false),
    });
}

function destroy() {
    if (!confirm(`Delete "${props.announcement.title}"?`)) return;
    router.delete(route('organizations.announcements.destroy', [props.organizationId, props.announcement.id]), { preserveScroll: true });
}
</script>

<template>
    <div class="bg-white border rounded-xl p-5"
        :class="[announcement.is_pinned ? 'border-secondary-300' : 'border-neutral-200', announcement.is_important ? 'border-l-4 border-l-red-500' : '']">
        <div v-if="announcement.is_important" class="flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-red-600 mb-1.5">
            <FlagIcon class="w-3 h-3" /> Important
        </div>
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2">
                <MapPinIcon v-if="announcement.is_pinned" class="w-4 h-4 text-secondary-500" />
                <h3 class="font-heading font-bold text-tertiary-900">{{ announcement.title }}</h3>
                <span v-if="!canManage && !announcement.read_by_me" class="w-2 h-2 rounded-full bg-primary-500" title="Unread"></span>
            </div>
            <div v-if="canManage" class="flex items-center gap-3 shrink-0">
                <button type="button" :disabled="nudging" @click="nudge"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-tertiary-400 hover:text-primary-600 disabled:opacity-50">
                    <BellAlertIcon class="w-3.5 h-3.5" /> Nudge Unread
                </button>
                <button type="button" @click="emit('edit', announcement)" class="text-tertiary-300 hover:text-primary-600">
                    <PencilSquareIcon class="w-4 h-4" />
                </button>
                <button type="button" @click="destroy" class="text-tertiary-300 hover:text-red-500">
                    <TrashIcon class="w-4 h-4" />
                </button>
            </div>
        </div>

        <div class="mt-2 flex flex-col sm:flex-row items-start gap-4">
            <div class="min-w-0 flex-1">
                <AnnouncementBody :html="announcement.body" />
            </div>
            <a v-if="primaryAttachment" :href="primaryAttachment.url" target="_blank" rel="noopener noreferrer"
                class="block w-full sm:w-36 aspect-[4/5] rounded-xl overflow-hidden border border-neutral-200 shadow-soft shrink-0 hover:border-primary-300 hover:shadow-elevated transition-all duration-200">
                <img v-if="primaryAttachment.mime_type && primaryAttachment.mime_type.startsWith('image/')"
                    :src="primaryAttachment.processed_url ?? primaryAttachment.url" :alt="primaryAttachment.name" class="w-full h-full object-cover" />
                <span v-else class="w-full h-full flex items-center justify-center bg-neutral-50">
                    <DocumentIcon class="w-12 h-12 text-primary-500" />
                </span>
            </a>
        </div>

        <a v-if="announcement.link_url" :href="announcement.link_url" target="_blank" rel="noopener noreferrer"
            class="mt-3 flex items-center gap-2 rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm font-semibold text-primary-600 hover:bg-primary-50 hover:border-primary-200 transition-colors duration-200 w-fit">
            <LinkIcon class="w-4 h-4 shrink-0" /> {{ announcement.link_url }}
        </a>

        <div v-if="remainingAttachments.length" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
            <template v-for="att in remainingAttachments" :key="att.id">
                <a v-if="att.mime_type && att.mime_type.startsWith('image/')" :href="att.url" target="_blank" rel="noopener noreferrer"
                    class="block rounded-lg overflow-hidden border border-neutral-200 hover:border-primary-300 transition-colors duration-200">
                    <img :src="att.processed_url ?? att.url" :alt="att.name" class="w-full aspect-[4/5] object-cover" />
                    <p class="px-2 py-1.5 text-xs text-tertiary-500 truncate">{{ att.name }}</p>
                </a>
                <a v-else :href="att.url" target="_blank" rel="noopener noreferrer"
                    class="flex items-center gap-2.5 rounded-lg border border-neutral-200 px-3 py-2 hover:border-primary-300 hover:bg-primary-50/40 transition-colors duration-200">
                    <DocumentIcon class="w-5 h-5 text-primary-500 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold text-tertiary-700 truncate">{{ att.name }}</p>
                        <p class="text-[11px] text-tertiary-400">{{ att.size }}</p>
                    </div>
                    <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5 text-tertiary-300 shrink-0" />
                </a>
            </template>
        </div>

        <div class="flex items-center justify-between mt-3">
            <p class="text-xs text-tertiary-400">{{ announcement.author }} &middot; {{ announcement.posted_at }}</p>
            <button v-if="canManage" type="button" @click="receiptsOpen = !receiptsOpen" class="flex items-center gap-1 text-xs font-semibold text-tertiary-400 hover:text-tertiary-800">
                Read {{ announcement.read_count }} / {{ announcement.total_members }}
                <ChevronDownIcon class="w-3.5 h-3.5 transition-transform" :class="receiptsOpen ? 'rotate-180' : ''" />
            </button>
        </div>
        <div v-if="canManage" class="mt-2 h-1 rounded-full bg-neutral-100 overflow-hidden">
            <div class="h-full bg-primary-500" :style="{ width: `${announcement.total_members ? (announcement.read_count / announcement.total_members) * 100 : 0}%` }"></div>
        </div>

        <div v-if="canManage && receiptsOpen" class="mt-4 pt-4 border-t border-neutral-100">
            <p class="text-[11px] font-bold uppercase tracking-wide text-tertiary-400 mb-2">Read Receipts</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1.5">
                <div v-for="r in announcement.receipts" :key="r.name" class="flex items-center justify-between gap-2 text-xs">
                    <span class="flex items-center gap-1.5 text-tertiary-600 truncate">
                        <CheckCircleIcon v-if="r.is_read" class="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                        <span v-else class="w-3.5 h-3.5 rounded-full border border-tertiary-200 shrink-0"></span>
                        {{ r.name }}
                    </span>
                    <span class="text-tertiary-400 shrink-0">{{ r.is_read ? r.read_at : 'Unread' }}</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 mt-3 pt-3 border-t border-neutral-100 flex-nowrap">
            <ReactionButton :organization-id="organizationId" :announcement-id="announcement.id"
                :count="announcement.reaction_count" :reacted-by-me="announcement.reacted_by_me" />
            <button type="button" @click="commentsOpen = !commentsOpen"
                class="inline-flex items-center gap-1.5 text-xs font-semibold transition-colors duration-200 shrink-0"
                :class="commentsOpen ? 'text-primary-600' : 'text-tertiary-400 hover:text-primary-600'">
                <ChatBubbleLeftIcon class="w-4 h-4" />
                <span>{{ announcement.comment_count }}</span>
            </button>
        </div>

        <Transition
            enter-active-class="animate-fade-in-up"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <CommentThread v-if="commentsOpen" :organization-id="organizationId" :announcement="announcement" class="mt-3 pt-3 border-t border-neutral-100" />
        </Transition>
    </div>
</template>
