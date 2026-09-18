<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    organizationId: { type: String, required: true },
    item: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const form = useForm({ name: '' });

watch(() => props.show, (show) => {
    if (!show || !props.item) return;
    form.clearErrors();
    form.name = props.item.name;
});

function submit() {
    form.patch(route('organizations.files.rename', [props.organizationId, props.item.id]), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <Modal :show="show" max-width="sm" @close="emit('close')">
        <form v-if="item" @submit.prevent="submit" class="p-6 space-y-5">
            <div>
                <h2 class="font-heading text-lg font-bold text-tertiary-900">Rename {{ item.is_folder ? 'Folder' : 'File' }}</h2>
            </div>

            <div>
                <InputLabel for="item-name" value="Name" />
                <TextInput id="item-name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                <InputError :message="form.errors.name" class="mt-1" />
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <SecondaryButton type="button" @click="emit('close')">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
