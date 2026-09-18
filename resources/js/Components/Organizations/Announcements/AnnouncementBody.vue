<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps({
    html: { type: String, required: true },
});

const COLLAPSED_MAX = 96; // px, roughly 4 lines of body text

const bodyEl = ref(null);
const isTruncatable = ref(false);
const expanded = ref(false);
const maxHeight = ref(null); // null = no clamp yet (avoids a flash of clipped content pre-measure)

async function measure() {
    await nextTick();
    if (!bodyEl.value) return;

    const fullHeight = bodyEl.value.scrollHeight;
    isTruncatable.value = fullHeight > COLLAPSED_MAX + 8; // small tolerance
    maxHeight.value = expanded.value ? fullHeight : (isTruncatable.value ? COLLAPSED_MAX : fullHeight);
}

function toggle() {
    expanded.value = !expanded.value;
    maxHeight.value = expanded.value ? bodyEl.value.scrollHeight : COLLAPSED_MAX;
}

onMounted(measure);
watch(() => props.html, () => {
    expanded.value = false;
    measure();
});
</script>

<template>
    <div>
        <div class="relative overflow-hidden transition-[max-height] duration-300 ease-ios"
            :style="maxHeight !== null ? { maxHeight: maxHeight + 'px' } : {}">
            <div ref="bodyEl" class="announcement-body text-sm text-tertiary-600" v-html="html"></div>
            <div v-if="isTruncatable && !expanded"
                class="pointer-events-none absolute inset-x-0 bottom-0 h-8 bg-gradient-to-t from-white to-transparent"></div>
        </div>
        <button v-if="isTruncatable" type="button" @click="toggle"
            class="mt-1 text-xs font-semibold text-primary-600 hover:text-primary-700 transition-colors duration-150">
            {{ expanded ? 'See less' : 'See more' }}
        </button>
    </div>
</template>

<style>
.announcement-body p { margin: 0 0 0.5rem; }
.announcement-body p:last-child { margin-bottom: 0; }
.announcement-body ul { list-style: disc; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.announcement-body ol { list-style: decimal; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.announcement-body blockquote {
    border-left: 3px solid #2E418D;
    padding-left: 0.75rem;
    margin: 0.5rem 0;
    color: #4B5563;
    font-style: italic;
}
.announcement-body mark { border-radius: 0.2rem; padding: 0 0.15rem; }
.announcement-body a { color: #2E418D; text-decoration: underline; }
</style>
