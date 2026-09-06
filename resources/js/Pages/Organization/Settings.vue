<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    organization: Object
});

const form = useForm({
    name: props.organization.name,
    logo: null,
    primary_color: props.organization.branding?.primary_color || '#4F46E5',
    modules: props.organization.settings?.enabled_modules || []
});

const submit = () => {
    form.post(route('organization.settings.update'));
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>Organization Settings</template>

        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow p-6">
            <form @submit.prevent="submit" class="space-y-6">
                
                <!-- Basic Info -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Basic Information</h3>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Organization Name</label>
                            <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Logo Upload</label>
                            <input type="file" @input="form.logo = $event.target.files[0]" class="mt-1 block w-full">
                        </div>
                    </div>
                </div>

                <!-- Branding -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Brand Theme</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Primary Color</label>
                        <input v-model="form.primary_color" type="color" class="mt-1 block h-10 w-20 rounded-md">
                    </div>
                </div>

                <!-- Modular Toggles -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Enable Features</h3>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.modules" value="attendance" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                            <span class="ml-2 text-sm text-gray-600">Attendance Tracking</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.modules" value="files" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                            <span class="ml-2 text-sm text-gray-600">File Storage</span>
                        </label>
                         <label class="flex items-center">
                            <input type="checkbox" v-model="form.modules" value="calendar" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                            <span class="ml-2 text-sm text-gray-600">Events & Calendar</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>