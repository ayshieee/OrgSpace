<script setup>
import FileCard from './FileCard.vue';

defineProps({
    items: { type: Array, required: true },
});

defineEmits(['open', 'rename', 'delete', 'toggle-restriction', 'open-office', 'manage-permissions']);
</script>

<template>
    <div v-if="items.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <FileCard v-for="item in items" :key="item.id" :item="item" :tier="item.permission_tier"
            @open="$emit('open', $event)" @rename="$emit('rename', $event)"
            @delete="$emit('delete', $event)" @toggle-restriction="$emit('toggle-restriction', $event)"
            @open-office="$emit('open-office', $event)" @manage-permissions="$emit('manage-permissions', $event)" />
    </div>
    <p v-else class="text-center text-sm text-tertiary-400 py-16 bg-white border border-neutral-200/60 rounded-2xl shadow-soft">
        No files or folders yet.
    </p>
</template>
