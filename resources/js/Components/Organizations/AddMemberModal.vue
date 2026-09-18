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
    roles: { type: Array, required: true },
});

const emit = defineEmits(['close']);

const form = useForm({
    first_name: '',
    m_i: '',
    surname: '',
    student_id: '',
    email: '',
    role_id: '',
});

watch(() => props.show, (show) => {
    if (!show) return;
    form.clearErrors();
});

function submit() {
    form.post(route('organizations.members.store', props.organizationId), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
}
</script>

<template>
    <Modal :show="show" max-width="lg" @close="emit('close')">
        <form @submit.prevent="submit" class="p-6 space-y-5">
            <div>
                <h2 class="font-heading text-lg font-bold text-tertiary-900">Add Member</h2>
                <p class="mt-0.5 text-sm text-tertiary-500">Creates a real account and emails their login credentials — the same process as the setup wizard.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-[1fr_80px_1fr] gap-4">
                <div>
                    <InputLabel for="add-member-first-name" value="First Name" />
                    <TextInput id="add-member-first-name" v-model="form.first_name" type="text" class="mt-1 block w-full" required autofocus />
                    <InputError :message="form.errors.first_name" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="add-member-mi" value="M.I." />
                    <TextInput id="add-member-mi" v-model="form.m_i" type="text" class="mt-1 block w-full" maxlength="5" />
                    <InputError :message="form.errors.m_i" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="add-member-surname" value="Surname" />
                    <TextInput id="add-member-surname" v-model="form.surname" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.surname" class="mt-1" />
                </div>
            </div>

            <div>
                <InputLabel for="add-member-email" value="Gmail or School Email" />
                <TextInput id="add-member-email" v-model="form.email" type="email" class="mt-1 block w-full" required placeholder="student@nu-laguna.edu.ph" />
                <InputError :message="form.errors.email" class="mt-1" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="add-member-student-id" value="Student ID" />
                    <TextInput id="add-member-student-id" v-model="form.student_id" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.student_id" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="add-member-role" value="Role" />
                    <select id="add-member-role" v-model="form.role_id"
                        class="mt-1 block w-full rounded-md border-tertiary-200 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">No role</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                    </select>
                    <InputError :message="form.errors.role_id" class="mt-1" />
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <SecondaryButton type="button" @click="emit('close')">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing">Add Member</PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
