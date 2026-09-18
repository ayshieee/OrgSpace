<script setup>
const props = defineProps({
    modelValue: { type: Object, required: true }, // { view: string|null, edit: string|null }
    permissionKeys: { type: Array, required: true }, // [{ key, label }]
    showCascadeOption: { type: Boolean, default: false },
    cascade: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue', 'update:cascade']);

function updateView(value) {
    emit('update:modelValue', { ...props.modelValue, view: value === '' ? null : value });
}

function updateEdit(value) {
    emit('update:modelValue', { ...props.modelValue, edit: value === '' ? null : value });
}
</script>

<template>
    <div class="space-y-3">
        <div>
            <label class="block text-xs font-semibold text-tertiary-700 mb-1">Who can view</label>
            <select :value="modelValue.view ?? ''" @change="updateView($event.target.value)"
                class="w-full text-sm rounded-lg border-neutral-200 focus:border-primary-500 focus:ring-primary-500">
                <option value="">Anyone in the organization</option>
                <option v-for="p in permissionKeys" :key="p.key" :value="p.key">Members with: {{ p.label }}</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-tertiary-700 mb-1">Who can edit</label>
            <select :value="modelValue.edit ?? ''" @change="updateEdit($event.target.value)"
                class="w-full text-sm rounded-lg border-neutral-200 focus:border-primary-500 focus:ring-primary-500">
                <option value="">Managers only</option>
                <option v-for="p in permissionKeys" :key="p.key" :value="p.key">Members with: {{ p.label }}</option>
            </select>
            <p class="text-[11px] text-tertiary-400 mt-1">
                Manage (delete, restrict, change permissions) always requires the organization's Manage Files permission and can't be delegated per file.
            </p>
        </div>
        <label v-if="showCascadeOption" class="flex items-center gap-2 text-xs text-tertiary-600 cursor-pointer">
            <input type="checkbox" :checked="cascade" @change="emit('update:cascade', $event.target.checked)"
                class="rounded border-tertiary-200 text-primary-600 focus:ring-primary-500" />
            Also apply to everything inside this folder
        </label>
    </div>
</template>
