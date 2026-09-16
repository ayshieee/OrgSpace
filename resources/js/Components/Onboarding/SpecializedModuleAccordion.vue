<script setup>
import { ref } from 'vue';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';

const props = defineProps({
    module: { type: Object, required: true },
    icon: { type: [Object, Function], default: null },
});

const emit = defineEmits(['toggle', 'update-setting']);

const expanded = ref(props.module.is_enabled);
const hasSettings = Object.keys(props.module.sub_settings ?? {}).length > 0;

function onToggleHeader() {
    expanded.value = !expanded.value;
}
</script>

<template>
    <div class="border border-neutral-200 rounded-xl bg-white overflow-hidden">
        <button type="button" @click="onToggleHeader" class="w-full flex items-center gap-3 px-4 py-3.5 text-left">
            <component :is="icon" v-if="icon" class="w-5 h-5 text-primary-500 shrink-0" />
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-tertiary-900">{{ module.name }}</p>
                <p class="text-xs text-tertiary-400 truncate">{{ module.description }}</p>
            </div>
            <ToggleSwitch :model-value="module.is_enabled" @update:model-value="emit('toggle')" @click.stop />
            <ChevronDownIcon class="w-4 h-4 text-tertiary-300 transition-transform shrink-0" :class="expanded ? 'rotate-180' : ''" />
        </button>

        <div v-if="expanded && module.is_enabled && hasSettings" class="px-4 pb-4 pt-1 border-t border-neutral-100 space-y-2">
            <label v-for="(setting, key) in module.sub_settings" :key="key" class="flex items-center justify-between text-xs">
                <span class="text-tertiary-600">{{ setting.label }}</span>
                <input type="checkbox" :checked="module.settings[key]" @change="emit('update-setting', key, $event.target.checked)"
                    class="rounded border-tertiary-200 text-primary-600 shadow-sm focus:ring-primary-500" />
            </label>
        </div>
    </div>
</template>
