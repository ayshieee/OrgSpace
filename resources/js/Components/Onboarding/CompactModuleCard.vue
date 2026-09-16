<script setup>
import { ref } from 'vue';
import { LockClosedIcon, CheckIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    module: { type: Object, required: true },
    icon: { type: [Object, Function], default: null },
});

const emit = defineEmits(['toggle', 'update-setting']);

const expanded = ref(false);
const hasSettings = Object.keys(props.module.sub_settings ?? {}).length > 0;
</script>

<template>
    <div class="relative rounded-xl border p-4 bg-white transition-colors"
        :class="module.is_enabled ? 'border-primary-200' : 'border-neutral-200'">
        <div class="absolute -top-2 -right-2 w-6 h-6 rounded-full flex items-center justify-center shadow-sm"
            :class="module.locked ? 'bg-neutral-200 text-tertiary-500' : module.is_enabled ? 'bg-primary text-white' : 'bg-neutral-100 text-tertiary-300'"
            @click="!module.locked && emit('toggle')"
            :role="module.locked ? undefined : 'button'"
        >
            <LockClosedIcon v-if="module.locked" class="w-3.5 h-3.5" />
            <CheckIcon v-else-if="module.is_enabled" class="w-3.5 h-3.5" />
        </div>

        <component :is="icon" v-if="icon" class="w-6 h-6 text-primary-500 mb-2" />
        <h3 class="font-semibold text-tertiary-900 text-sm">{{ module.name }}</h3>
        <p class="text-xs text-tertiary-500 mt-0.5">{{ module.description }}</p>

        <button v-if="hasSettings && module.is_enabled" type="button" @click="expanded = !expanded"
            class="mt-2 text-xs font-semibold text-primary-600 hover:text-primary-700">
            {{ expanded ? 'Hide options' : 'Configure' }}
        </button>

        <div v-if="expanded && hasSettings" class="mt-3 pt-3 border-t border-neutral-100 space-y-2">
            <label v-for="(setting, key) in module.sub_settings" :key="key" class="flex items-center justify-between text-xs">
                <span class="text-tertiary-600">{{ setting.label }}</span>
                <input type="checkbox" :checked="module.settings[key]" @change="emit('update-setting', key, $event.target.checked)"
                    class="rounded border-tertiary-200 text-primary-600 shadow-sm focus:ring-primary-500" />
            </label>
        </div>
    </div>
</template>
