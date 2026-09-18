<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import FilePermissionPicker from './FilePermissionPicker.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    organizationId: { type: String, required: true },
    item: { type: Object, default: null },
    permissionKeys: { type: Array, required: true },
});

const emit = defineEmits(['close']);

const form = useForm({ view_permission: null, edit_permission: null, cascade: true });
const cascade = ref(true);

watch(() => props.show, (show) => {
    if (!show || !props.item) return;
    form.clearErrors();
    form.view_permission = props.item.view_permission ?? null;
    form.edit_permission = props.item.edit_permission ?? null;
    cascade.value = true;
});

function updatePermissions(value) {
    form.view_permission = value.view;
    form.edit_permission = value.edit;
}

function submit() {
    form.cascade = cascade.value;
    form.post(route('organizations.files.permissions.update', [props.organizationId, props.item.id]), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <Modal :show="show" max-width="sm" @close="emit('close')">
        <form v-if="item" @submit.prevent="submit" class="p-6 space-y-5">
            <div>
                <h2 class="font-heading text-lg font-bold text-tertiary-900">Manage Permissions</h2>
                <p class="text-sm text-tertiary-400 mt-0.5">{{ item.name }}</p>
            </div>

            <FilePermissionPicker
                :model-value="{ view: form.view_permission, edit: form.edit_permission }"
                @update:model-value="updatePermissions"
                :permission-keys="permissionKeys"
                :show-cascade-option="item.is_folder"
                :cascade="cascade"
                @update:cascade="cascade = $event"
            />
            <InputError :message="form.errors.view_permission" />
            <InputError :message="form.errors.edit_permission" />

            <div class="flex justify-end gap-3 pt-2">
                <SecondaryButton type="button" @click="emit('close')">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
