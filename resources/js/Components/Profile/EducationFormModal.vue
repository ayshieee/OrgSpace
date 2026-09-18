<script setup>
import { watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: { type: Boolean, default: false },
    education: { type: Object, default: null }, // null = adding a new entry
});

const emit = defineEmits(['close']);

const form = useForm({
    institution: '',
    program: '',
    start_date: '',
    end_date: '',
    is_current: false,
});

watch(() => props.show, (show) => {
    if (!show) return;

    form.clearErrors();
    form.institution = props.education?.institution ?? '';
    form.program = props.education?.program ?? '';
    form.start_date = props.education?.start_date ?? '';
    form.end_date = props.education?.end_date ?? '';
    form.is_current = props.education?.is_current ?? false;
});

function submit() {
    const options = { preserveScroll: true, onSuccess: () => emit('close') };

    if (props.education) {
        form.patch(route('profile.education.update', props.education.id), options);
    } else {
        form.post(route('profile.education.store'), options);
    }
}
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <form @submit.prevent="submit" class="p-6 space-y-5">
            <h2 class="font-heading text-lg font-bold text-tertiary-900">
                {{ education ? 'Edit Education' : 'Add Education' }}
            </h2>

            <div>
                <InputLabel for="edu-institution" value="School / University" />
                <TextInput id="edu-institution" v-model="form.institution" type="text" class="mt-1 block w-full" required autofocus placeholder="e.g. National University Laguna" />
                <InputError :message="form.errors.institution" class="mt-1" />
            </div>

            <div>
                <InputLabel for="edu-program" value="Program / Course (optional)" />
                <TextInput id="edu-program" v-model="form.program" type="text" class="mt-1 block w-full" placeholder="e.g. BS Computer Science" />
                <InputError :message="form.errors.program" class="mt-1" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="edu-start" value="Start Date" />
                    <TextInput id="edu-start" v-model="form.start_date" type="date" class="mt-1 block w-full" />
                    <InputError :message="form.errors.start_date" class="mt-1" />
                </div>

                <div>
                    <InputLabel for="edu-end" value="End Date" />
                    <TextInput id="edu-end" v-model="form.end_date" type="date" class="mt-1 block w-full" :disabled="form.is_current" />
                    <InputError :message="form.errors.end_date" class="mt-1" />
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-tertiary-600 cursor-pointer">
                <input v-model="form.is_current" type="checkbox" class="rounded border-tertiary-200 text-primary-600 shadow-sm focus:ring-primary-500" />
                I'm currently studying here
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <SecondaryButton type="button" @click="emit('close')">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing">{{ education ? 'Save Changes' : 'Add Education' }}</PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
