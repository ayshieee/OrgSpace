<script setup>
import { ref } from 'vue';
import { PhotoIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: { type: [File, null], default: null },
    existingUrl: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue']);

const preview = ref(props.existingUrl);
const input = ref(null);

function onChange(event) {
    const file = event.target.files[0] ?? null;
    setFile(file);
}

function setFile(file) {
    if (!file) return;
    emit('update:modelValue', file);
    preview.value = URL.createObjectURL(file);
}

function onDrop(event) {
    setFile(event.dataTransfer.files[0] ?? null);
}
</script>

<template>
    <div class="flex flex-col items-center">
        <button
            type="button"
            @click="input.click()"
            @dragover.prevent
            @drop.prevent="onDrop"
            class="w-24 h-24 rounded-full border-2 border-dashed border-primary-300 bg-primary-50/40 flex flex-col items-center justify-center overflow-hidden hover:bg-primary-50 transition-colors"
        >
            <img v-if="preview" :src="preview" class="w-full h-full object-cover" alt="Organization logo preview" />
            <template v-else>
                <PhotoIcon class="w-6 h-6 text-primary-400" />
                <span class="mt-1 text-[10px] font-semibold text-primary-500">Upload Logo</span>
            </template>
        </button>
        <input ref="input" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="onChange" />
        <p class="mt-2 text-xs text-tertiary-400">Recommended: 400x400px PNG or JPG.</p>
    </div>
</template>
