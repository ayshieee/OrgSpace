<script setup>
import { router } from '@inertiajs/vue3';
import { DocumentIcon, LinkIcon, ArrowUturnLeftIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { getInitials, colorForId } from '@/utils/initials';
import CommentComposer from './CommentComposer.vue';

const props = defineProps({
    organizationId: { type: String, required: true },
    announcementId: { type: String, required: true },
    comment: { type: Object, required: true },
    depth: { type: Number, default: 0 },
    replyingTo: { type: String, default: null },
});

const emit = defineEmits(['reply', 'cancel-reply', 'replied']);

function destroy() {
    if (!confirm('Delete this comment?')) return;

    router.delete(route('organizations.announcements.comments.destroy', [props.organizationId, props.announcementId, props.comment.id]), {
        preserveScroll: true,
        only: ['announcements'],
    });
}
</script>

<template>
    <div class="flex items-start gap-2.5">
        <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 overflow-hidden"
            :class="comment.author.avatar_path ? '' : colorForId(comment.author.id)">
            <img v-if="comment.author.avatar_path" :src="`/storage/${comment.author.avatar_path}`" class="w-full h-full object-cover" :alt="comment.author.name" />
            <template v-else>{{ getInitials(comment.author.name) }}</template>
        </div>

        <div class="min-w-0 flex-1">
            <div class="rounded-2xl bg-neutral-50 px-3 py-2">
                <div class="flex items-center gap-1.5">
                    <p class="text-xs font-semibold text-tertiary-900">{{ comment.author.name }}</p>
                    <span class="text-[11px] text-tertiary-400">&middot; {{ comment.posted_at }}</span>
                </div>
                <p class="comment-body text-sm text-tertiary-700 whitespace-pre-wrap break-words" v-html="comment.body"></p>
            </div>

            <div v-if="comment.attachments.length" class="mt-1.5 flex flex-wrap gap-2">
                <template v-for="att in comment.attachments" :key="att.id">
                    <a v-if="att.type === 'image'" :href="att.url" target="_blank" rel="noopener noreferrer"
                        class="block w-24 h-24 rounded-lg overflow-hidden border border-neutral-200 hover:border-primary-300 transition-colors duration-200">
                        <img :src="att.url" :alt="att.name" class="w-full h-full object-cover" />
                    </a>
                    <a v-else-if="att.type === 'file'" :href="att.url" target="_blank" rel="noopener noreferrer"
                        class="flex items-center gap-1.5 rounded-lg border border-neutral-200 px-2.5 py-1.5 text-xs hover:border-primary-300 hover:bg-primary-50/40 transition-colors duration-200">
                        <DocumentIcon class="w-3.5 h-3.5 text-primary-500 shrink-0" />
                        <span class="font-semibold text-tertiary-700 truncate max-w-[140px]">{{ att.name }}</span>
                        <span class="text-tertiary-400 shrink-0">{{ att.size }}</span>
                    </a>
                    <a v-else :href="att.url" target="_blank" rel="noopener noreferrer"
                        class="flex items-center gap-1.5 rounded-lg border border-neutral-200 px-2.5 py-1.5 text-xs text-primary-600 font-semibold hover:border-primary-300 hover:bg-primary-50/40 transition-colors duration-200">
                        <LinkIcon class="w-3.5 h-3.5 shrink-0" />
                        <span class="truncate max-w-[160px]">{{ att.name }}</span>
                    </a>
                </template>
            </div>

            <div class="flex items-center gap-3 mt-1 pl-1">
                <button v-if="depth === 0" type="button" @click="emit('reply', comment.id)"
                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-tertiary-400 hover:text-primary-600 transition-colors duration-150">
                    <ArrowUturnLeftIcon class="w-3 h-3" /> Reply
                </button>
                <button v-if="comment.can_delete" type="button" @click="destroy"
                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-tertiary-400 hover:text-red-500 transition-colors duration-150">
                    <TrashIcon class="w-3 h-3" /> Delete
                </button>
            </div>

            <div v-if="replyingTo === comment.id" class="mt-2">
                <CommentComposer :organization-id="organizationId" :announcement-id="announcementId" :parent-id="comment.id" autofocus
                    @posted="emit('replied')" @cancel="emit('cancel-reply')" />
            </div>

            <div v-if="comment.replies && comment.replies.length" class="mt-3 space-y-3">
                <CommentItem v-for="reply in comment.replies" :key="reply.id"
                    :organization-id="organizationId" :announcement-id="announcementId" :comment="reply" :depth="1"
                    :replying-to="replyingTo" @reply="emit('reply', $event)" @cancel-reply="emit('cancel-reply')" @replied="emit('replied')" />
            </div>
        </div>
    </div>
</template>

<style>
.comment-body a { color: #2E418D; text-decoration: underline; }
</style>
