<script setup>
import { ref } from 'vue';

const emit = defineEmits(['select', 'close']);

const tab = ref('emoji');

// A curated set of real Unicode emoji — rendered by the OS's own emoji
// font (Apple Color Emoji on Mac/iOS, Noto Color Emoji on most others),
// so this genuinely looks native on Apple devices without embedding any
// proprietary Apple asset — that's just how Unicode text renders.
const categories = [
    {
        label: 'Smileys',
        items: ['😀', '😄', '😁', '😂', '🥲', '😊', '😉', '😍', '🥰', '😘', '😎', '🤔', '😴', '😢', '😭', '😡', '🥳', '😇', '🙃', '🤩'],
    },
    {
        label: 'Gestures',
        items: ['👍', '👎', '👏', '🙌', '🙏', '💪', '👋', '✌️', '🤝', '👌'],
    },
    {
        label: 'Objects & Symbols',
        items: ['🎉', '🎊', '📣', '📌', '📎', '📅', '✅', '❌', '⚠️', '🔔', '⭐', '❤️', '🔥', '💯', '🎵'],
    },
];

// "Stickers" — the honest alternative to proprietary iMessage stickers per
// the explicit fallback instruction: the same real emoji, presented large
// in their own tray, not a fabricated substitute.
const stickers = ['🎉', '🥳', '👍', '❤️', '🔥', '⭐', '🙌', '📣', '✅', '😍', '🎵', '💯'];

function pick(value) {
    emit('select', value);
}
</script>

<template>
    <div class="w-72 bg-white border border-neutral-200/60 rounded-2xl shadow-elevated overflow-hidden">
        <div class="flex items-center border-b border-neutral-100">
            <button type="button" @click="tab = 'emoji'"
                class="flex-1 px-3 py-2.5 text-xs font-semibold transition-colors duration-200"
                :class="tab === 'emoji' ? 'text-primary-700 border-b-2 border-primary-500' : 'text-tertiary-400 hover:text-tertiary-600'">
                Emoji
            </button>
            <button type="button" @click="tab = 'stickers'"
                class="flex-1 px-3 py-2.5 text-xs font-semibold transition-colors duration-200"
                :class="tab === 'stickers' ? 'text-primary-700 border-b-2 border-primary-500' : 'text-tertiary-400 hover:text-tertiary-600'">
                Stickers
            </button>
        </div>

        <div v-if="tab === 'emoji'" class="max-h-64 overflow-y-auto p-3 space-y-3">
            <div v-for="cat in categories" :key="cat.label">
                <p class="text-[10px] font-bold uppercase tracking-wide text-tertiary-400 mb-1.5">{{ cat.label }}</p>
                <div class="grid grid-cols-8 gap-1">
                    <button v-for="emoji in cat.items" :key="emoji" type="button" @click="pick(emoji)"
                        class="text-xl leading-none rounded-lg p-1.5 hover:bg-primary-50 hover:scale-110 transition-all duration-150 ease-ios">
                        {{ emoji }}
                    </button>
                </div>
            </div>
        </div>

        <div v-else class="max-h-64 overflow-y-auto p-3">
            <div class="grid grid-cols-4 gap-2">
                <button v-for="sticker in stickers" :key="sticker" type="button" @click="pick(sticker)"
                    class="text-4xl leading-none rounded-xl p-3 bg-neutral-50 hover:bg-primary-50 hover:scale-105 transition-all duration-150 ease-ios">
                    {{ sticker }}
                </button>
            </div>
        </div>
    </div>
</template>
