<script setup>
import { PencilIcon, PaintBrushIcon, BackspaceIcon, ArrowUturnLeftIcon, ArrowUturnRightIcon, XMarkIcon } from '@heroicons/vue/24/outline';

defineProps({
    tool: { type: String, default: null },
    color: { type: String, required: true },
    canUndo: { type: Boolean, default: false },
    canRedo: { type: Boolean, default: false },
});
const emit = defineEmits(['update:tool', 'update:color', 'undo', 'redo', 'clear']);

const colors = ['#2E418D', '#FFD217', '#DC2626', '#059669'];
</script>

<template>
    <div class="flex items-center gap-2 flex-wrap bg-white border border-neutral-200 rounded-lg px-3 py-2">
        <button type="button" @click="emit('update:tool', tool === 'pen' ? null : 'pen')"
            class="p-1.5 rounded" :class="tool === 'pen' ? 'bg-primary-50 text-primary-600' : 'text-tertiary-400 hover:bg-neutral-100'" title="Pen">
            <PencilIcon class="w-4 h-4" />
        </button>
        <button type="button" @click="emit('update:tool', tool === 'highlight' ? null : 'highlight')"
            class="p-1.5 rounded" :class="tool === 'highlight' ? 'bg-primary-50 text-primary-600' : 'text-tertiary-400 hover:bg-neutral-100'" title="Highlighter">
            <PaintBrushIcon class="w-4 h-4" />
        </button>
        <button type="button" @click="emit('update:tool', tool === 'eraser' ? null : 'eraser')"
            class="p-1.5 rounded" :class="tool === 'eraser' ? 'bg-primary-50 text-primary-600' : 'text-tertiary-400 hover:bg-neutral-100'" title="Eraser">
            <BackspaceIcon class="w-4 h-4" />
        </button>

        <div class="w-px h-5 bg-neutral-200 mx-1" />

        <button v-for="c in colors" :key="c" type="button" @click="emit('update:color', c)"
            class="w-5 h-5 rounded-full border-2" :class="color === c ? 'border-tertiary-700' : 'border-white'"
            :style="{ backgroundColor: c }" :title="c" />

        <div class="w-px h-5 bg-neutral-200 mx-1" />

        <button type="button" :disabled="!canUndo" @click="emit('undo')" class="p-1.5 rounded text-tertiary-400 hover:bg-neutral-100 disabled:opacity-30" title="Undo">
            <ArrowUturnLeftIcon class="w-4 h-4" />
        </button>
        <button type="button" :disabled="!canRedo" @click="emit('redo')" class="p-1.5 rounded text-tertiary-400 hover:bg-neutral-100 disabled:opacity-30" title="Redo">
            <ArrowUturnRightIcon class="w-4 h-4" />
        </button>
        <button type="button" @click="emit('clear')" class="p-1.5 rounded text-tertiary-400 hover:bg-red-50 hover:text-red-500" title="Clear all annotations">
            <XMarkIcon class="w-4 h-4" />
        </button>
    </div>
</template>
