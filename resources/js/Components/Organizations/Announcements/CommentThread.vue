<script setup>
import { ref } from 'vue';
import CommentComposer from './CommentComposer.vue';
import CommentItem from './CommentItem.vue';

defineProps({
    organizationId: { type: String, required: true },
    announcement: { type: Object, required: true },
});

const replyingTo = ref(null);
</script>

<template>
    <div class="space-y-3">
        <CommentItem v-for="comment in announcement.comments" :key="comment.id"
            :organization-id="organizationId" :announcement-id="announcement.id" :comment="comment" :depth="0"
            :replying-to="replyingTo"
            @reply="replyingTo = $event"
            @cancel-reply="replyingTo = null"
            @replied="replyingTo = null" />

        <CommentComposer :organization-id="organizationId" :announcement-id="announcement.id" />
    </div>
</template>
