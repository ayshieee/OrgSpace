<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthSplitLayout from '@/Layouts/AuthSplitLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log In" />

    <AuthSplitLayout active="login">
        <h2 class="font-heading text-2xl font-extrabold text-tertiary-900 tracking-tight">Welcome back</h2>
        <p class="mt-1.5 text-sm text-tertiary-500">Please enter your details to sign in.</p>

        <div v-if="status" class="mt-6 font-medium text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 p-3 rounded-lg">
            {{ status }}
        </div>

        <div v-if="form.errors.email" class="mt-6 font-medium text-sm text-red-700 bg-red-50 border border-red-200 p-3 rounded-lg">
            {{ form.errors.email }}
        </div>

        <form @submit.prevent="submit" class="mt-6 space-y-5">
            <div>
                <InputLabel for="email" value="Email Address" />
                <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required autofocus placeholder="you@school.edu" />
            </div>

            <div>
                <div class="flex justify-between items-center">
                    <InputLabel for="password" value="Password" />
                    <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                        Forgot password?
                    </Link>
                </div>
                <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" required />
                <InputError :message="form.errors.password" class="mt-1" />
            </div>

            <label class="flex items-center gap-2 text-sm text-tertiary-600 cursor-pointer">
                <input v-model="form.remember" type="checkbox" class="rounded border-tertiary-200 text-primary-600 shadow-sm focus:ring-primary-500" />
                Remember me
            </label>

            <PrimaryButton class="w-full justify-center py-3" :disabled="form.processing">
                {{ form.processing ? 'Logging in...' : 'Log In' }}
            </PrimaryButton>
        </form>
    </AuthSplitLayout>
</template>
