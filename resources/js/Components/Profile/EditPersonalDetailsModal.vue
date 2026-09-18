<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: { type: Boolean, default: false },
    user: { type: Object, required: true },
    mustVerifyEmail: { type: Boolean, default: false },
    status: { type: String, default: null },
});

const emit = defineEmits(['close']);

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    bio: props.user.bio ?? '',
    pronouns: props.user.pronouns ?? '',
    phone_number: props.user.phone_number ?? '',
    location: props.user.location ?? '',
});

function submit() {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <Modal :show="show" max-width="lg" @close="emit('close')">
        <form @submit.prevent="submit" class="p-6 space-y-5">
            <h2 class="font-heading text-lg font-bold text-tertiary-900">Edit Profile</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="edit-name" value="Name" />
                    <TextInput id="edit-name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                    <InputError :message="form.errors.name" class="mt-1" />
                </div>

                <div>
                    <InputLabel for="edit-pronouns" value="Pronouns (optional)" />
                    <TextInput id="edit-pronouns" v-model="form.pronouns" type="text" class="mt-1 block w-full" placeholder="e.g. she/her" />
                    <InputError :message="form.errors.pronouns" class="mt-1" />
                </div>
            </div>

            <div>
                <InputLabel for="edit-email" value="Email" />
                <TextInput id="edit-email" v-model="form.email" type="email" class="mt-1 block w-full" required />
                <InputError :message="form.errors.email" class="mt-1" />

                <div v-if="mustVerifyEmail && user.email_verified_at === null" class="mt-1.5">
                    <p class="text-xs text-tertiary-500">
                        Your email address is unverified.
                        <Link :href="route('verification.send')" method="post" as="button"
                            class="font-semibold text-primary-600 hover:text-primary-700 underline">
                            Resend verification email
                        </Link>
                    </p>
                    <p v-show="status === 'verification-link-sent'" class="mt-1 text-xs font-medium text-emerald-600">
                        A new verification link has been sent.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="edit-phone" value="Phone Number (optional)" />
                    <TextInput id="edit-phone" v-model="form.phone_number" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.phone_number" class="mt-1" />
                </div>

                <div>
                    <InputLabel for="edit-location" value="Location (optional)" />
                    <TextInput id="edit-location" v-model="form.location" type="text" class="mt-1 block w-full" placeholder="e.g. Tanauan, Batangas" />
                    <InputError :message="form.errors.location" class="mt-1" />
                </div>
            </div>

            <div>
                <InputLabel for="edit-bio" value="Bio (optional)" />
                <textarea id="edit-bio" v-model="form.bio" rows="3" maxlength="500"
                    class="mt-1 block w-full rounded-md border-tertiary-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    placeholder="A short line about yourself..."></textarea>
                <InputError :message="form.errors.bio" class="mt-1" />
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <SecondaryButton type="button" @click="emit('close')">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
