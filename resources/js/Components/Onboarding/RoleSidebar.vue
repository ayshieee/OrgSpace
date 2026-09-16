<script setup>
import { ChevronRightIcon, TrashIcon, PlusIcon } from '@heroicons/vue/24/outline';

defineProps({
    roles: { type: Array, required: true },
    activeIndex: { type: Number, required: true },
});

const emit = defineEmits(['select', 'remove', 'add']);

const knownSubtitles = {
    adviser: 'Full Administrative Access',
    officer: 'Management & Event Creation',
    member: 'Basic Access',
};

function subtitle(role) {
    return knownSubtitles[role.name.trim().toLowerCase()]
        ?? `${role.permissions.length} permission${role.permissions.length === 1 ? '' : 's'}`;
}
</script>

<template>
  <div>
    <div class="border border-neutral-200 rounded-xl bg-white overflow-hidden">
        <button
            v-for="(role, index) in roles"
            :key="role.id ?? index"
            type="button"
            @click="emit('select', index)"
            class="w-full flex items-center justify-between gap-2 px-4 py-3 text-left border-b border-neutral-100 last:border-0 transition-colors"
            :class="index === activeIndex ? 'bg-primary-50 border-l-4 border-l-primary' : 'border-l-4 border-l-transparent hover:bg-neutral-50'"
        >
            <div class="min-w-0">
                <p class="text-sm font-semibold truncate" :class="index === activeIndex ? 'text-primary-700' : 'text-tertiary-800'">{{ role.name }}</p>
                <p class="text-xs text-tertiary-400 truncate">{{ subtitle(role) }}</p>
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <TrashIcon v-if="role.isCustom" class="w-4 h-4 text-tertiary-300 hover:text-red-500" @click.stop="emit('remove', index)" />
                <ChevronRightIcon v-if="index === activeIndex" class="w-4 h-4 text-primary-500" />
            </div>
        </button>
    </div>

    <button type="button" @click="emit('add')"
        class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-primary-600 hover:text-primary-700">
        <PlusIcon class="w-4 h-4" /> Add Custom Role
    </button>
  </div>
</template>
