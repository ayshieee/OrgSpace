<script setup>
import { ref, watch } from 'vue';
import { PhotoIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: { type: [File, null], default: null },
    existingUrl: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue', 'remove']);

const preview = ref(props.existingUrl);
const input = ref(null);

watch(() => props.existingUrl, (url) => {
    if (!props.modelValue) preview.value = url;
});

function onChange(event) {
    setFile(event.target.files[0] ?? null);
    event.target.value = '';
}

function onDrop(event) {
    setFile(event.dataTransfer.files[0] ?? null);
}

function setFile(file) {
    if (!file) return;
    emit('update:modelValue', file);
    preview.value = URL.createObjectURL(file);
}

function remove() {
    emit('update:modelValue', null);
    preview.value = null;
    emit('remove');
}
</script>

<template>
    <div>
        <button type="button" @click="input.click()" @dragover.prevent @drop.prevent="onDrop"
            class="relative w-full aspect-[16/7] rounded-xl border-2 border-dashed border-neutral-200 bg-neutral-50 hover:bg-primary-50/30 hover:border-primary-300 transition-colors duration-200 overflow-hidden flex flex-col items-center justify-center gap-1.5">
            <img v-if="preview" :src="preview" class="absolute inset-0 w-full h-full object-cover" alt="Event cover preview" />
            <template v-else>
                <PhotoIcon class="w-6 h-6 text-primary-400" />
                <span class="text-xs font-semibold text-primary-500">Upload Event Image</span>
                <span class="text-[11px] text-tertiary-400">Optional &middot; JPG, PNG, or WEBP</span>
            </template>
        </button>
        <button v-if="preview" type="button" @click="remove"
            class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-tertiary-400 hover:text-red-500 transition-colors">
            <XMarkIcon class="w-3.5 h-3.5" /> Remove image
        </button>
        <input ref="input" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onChange" />
    </div>
</template>
