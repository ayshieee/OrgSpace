<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { XMarkIcon, LockClosedIcon } from '@heroicons/vue/24/outline';
import AnnotationCanvas from './AnnotationCanvas.vue';
import MusicAnnotationToolbar from './MusicAnnotationToolbar.vue';

const props = defineProps({
    organizationId: { type: String, required: true },
    entry: { type: Object, required: true },
});
const emit = defineEmits(['close']);

const CANVAS_WIDTH = 800;
const CANVAS_HEIGHT = 1035; // roughly a US-letter page aspect ratio

const isImage = computed(() => (props.entry.file_type || '').startsWith('image/'));

const strokes = ref(props.entry.annotations?.['1'] ?? []);
const undone = ref([]);
const tool = ref(null);
const color = ref('#2E418D');
const dirty = ref(false);

watch(() => props.entry.id, () => {
    strokes.value = props.entry.annotations?.['1'] ?? [];
    undone.value = [];
    tool.value = null;
    dirty.value = false;
});

function addStroke(stroke) {
    strokes.value = [...strokes.value, stroke];
    undone.value = [];
    dirty.value = true;
}

function eraseStroke(id) {
    strokes.value = strokes.value.filter((s) => s.id !== id);
    dirty.value = true;
}

function undo() {
    if (!strokes.value.length) return;
    const last = strokes.value[strokes.value.length - 1];
    strokes.value = strokes.value.slice(0, -1);
    undone.value = [...undone.value, last];
    dirty.value = true;
}

function redo() {
    if (!undone.value.length) return;
    const last = undone.value[undone.value.length - 1];
    undone.value = undone.value.slice(0, -1);
    strokes.value = [...strokes.value, last];
    dirty.value = true;
}

function clearAll() {
    if (!strokes.value.length) return;
    if (!confirm('Clear all annotations on this score?')) return;
    strokes.value = [];
    undone.value = [];
    dirty.value = true;
}

function save() {
    router.patch(route('organizations.music.annotations', [props.organizationId, props.entry.id]), {
        annotations: { 1: strokes.value },
    }, {
        preserveScroll: true,
        onSuccess: () => { dirty.value = false; },
    });
}
</script>

<template>
    <div class="fixed inset-0 z-50 bg-tertiary-900/60 flex items-center justify-center p-4" @click.self="emit('close')">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-neutral-200 shrink-0">
                <div class="min-w-0">
                    <p class="font-heading font-bold text-tertiary-900 truncate">{{ entry.title }}</p>
                    <p class="text-xs text-tertiary-400">{{ [entry.composer, entry.section].filter(Boolean).join(' · ') || '&nbsp;' }}</p>
                </div>
                <button type="button" @click="emit('close')" class="text-tertiary-400 hover:text-tertiary-700 shrink-0 ml-3">
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>

            <div v-if="isImage" class="px-5 pt-3 shrink-0">
                <MusicAnnotationToolbar
                    v-model:tool="tool" v-model:color="color"
                    :can-undo="strokes.length > 0" :can-redo="undone.length > 0"
                    @undo="undo" @redo="redo" @clear="clearAll"
                />
            </div>

            <div class="flex-1 overflow-auto bg-neutral-50 p-5 flex items-center justify-center">
                <div v-if="isImage" class="relative bg-white shadow-sm w-full"
                    :style="{ maxWidth: CANVAS_WIDTH + 'px', aspectRatio: `${CANVAS_WIDTH} / ${CANVAS_HEIGHT}` }">
                    <img :src="entry.url" class="absolute inset-0 w-full h-full object-contain" alt="" />
                    <AnnotationCanvas
                        :width="CANVAS_WIDTH" :height="CANVAS_HEIGHT"
                        :strokes="strokes" :tool="tool" :color="color" editable
                        @add-stroke="addStroke" @erase-stroke="eraseStroke"
                    />
                </div>
                <div v-else-if="entry.url" class="w-full h-[70vh]">
                    <iframe :src="entry.url" class="w-full h-full rounded border border-neutral-200" title="Score preview" />
                </div>
                <div v-else class="text-center text-tertiary-400 text-sm py-10">
                    <LockClosedIcon class="w-6 h-6 mx-auto mb-2" />
                    This score is view-only for officers.
                </div>
            </div>

            <p v-if="!isImage && entry.url" class="px-5 pb-2 text-xs text-tertiary-400 shrink-0">
                Annotation is available for image scores (JPG/PNG). PDFs open for viewing only.
            </p>

            <div v-if="isImage" class="flex items-center justify-end gap-2 px-5 py-3 border-t border-neutral-200 shrink-0">
                <button type="button" @click="save" :disabled="!dirty"
                    class="inline-flex items-center rounded-lg bg-gradient-to-b from-secondary-400 to-secondary-500 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-tertiary-900 shadow-soft transition-all duration-200 ease-ios hover:shadow-elevated hover:from-secondary-300 hover:to-secondary-400 active:scale-[0.98] disabled:opacity-40">
                    {{ dirty ? 'Save Annotations' : 'Saved' }}
                </button>
            </div>
        </div>
    </div>
</template>
