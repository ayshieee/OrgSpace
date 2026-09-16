<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthSplitLayout from '@/Layouts/AuthSplitLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
    name: '',
    email: '',
    role: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Sign Up" />

    <AuthSplitLayout active="signup">
        <h2 class="font-heading text-2xl font-extrabold text-tertiary-900 tracking-tight">Create your account</h2>
        <p class="mt-1.5 text-sm text-tertiary-500">Join OrgSpace and start managing your student organization today.</p>

        <form @submit.prevent="submit" class="mt-6 space-y-5">
            <div>
                <InputLabel for="name" value="Full Name" />
                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus placeholder="Juan Dela Cruz" />
                <InputError :message="form.errors.name" class="mt-1" />
            </div>

            <div>
                <InputLabel for="role" value="Your Role" />
                <select id="role" v-model="form.role" required
                    class="mt-1 block w-full rounded-md border-tertiary-200 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="" disabled>Select your role&hellip;</option>
                    <option value="adviser">Adviser</option>
                    <option value="officer">Officer</option>
                </select>
                <InputError :message="form.errors.role" class="mt-1" />
                <p class="mt-1 text-xs text-tertiary-400">If you create an organization, this will be your role there.</p>
            </div>

            <div>
                <InputLabel for="email" value="Email Address" />
                <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required placeholder="student@nu-laguna.edu.ph" />
                <InputError :message="form.errors.email" class="mt-1" />
            </div>

            <div>
                <InputLabel for="password" value="Password" />
                <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" required />
                <InputError :message="form.errors.password" class="mt-1" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full" required />
                <InputError :message="form.errors.password_confirmation" class="mt-1" />
            </div>

            <PrimaryButton class="w-full justify-center py-3" :disabled="form.processing">
                {{ form.processing ? 'Creating account...' : 'Create Account' }}
            </PrimaryButton>
        </form>
    </AuthSplitLayout>
</template>
