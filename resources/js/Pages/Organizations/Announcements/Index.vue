<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import NewAnnouncementModal from '@/Components/Organizations/NewAnnouncementModal.vue';
import AnnouncementCard from '@/Components/Organizations/Announcements/AnnouncementCard.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    announcements: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
});

const showModal = ref(false);
const editing = ref(null);

function startCreate() {
    editing.value = null;
    showModal.value = true;
}

function startEdit(announcement) {
    editing.value = announcement;
    showModal.value = true;
}
</script>

<template>
    <Head title="Announcements" />

    <AuthenticatedLayout>
        <template #header>Announcements</template>

        <div class="flex items-center justify-between mb-5">
            <h1 class="font-heading text-xl font-extrabold text-tertiary-900">Announcements</h1>
            <PrimaryButton v-if="canManage" type="button" @click="startCreate">
                <PlusIcon class="w-4 h-4 mr-1.5" /> New Announcement
            </PrimaryButton>
        </div>

        <div class="space-y-4">
            <AnnouncementCard v-for="a in announcements" :key="a.id" :announcement="a"
                :organization-id="organization.id" :can-manage="canManage" @edit="startEdit" />

            <p v-if="!announcements.length" class="text-center text-sm text-tertiary-400 py-10 bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300">
                No announcements yet.
            </p>
        </div>

        <NewAnnouncementModal v-if="canManage" :show="showModal" :editing="editing" :organization-id="organization.id" @close="showModal = false" />
    </AuthenticatedLayout>
</template>
