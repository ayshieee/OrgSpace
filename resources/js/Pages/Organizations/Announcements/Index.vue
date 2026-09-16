<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { PlusIcon, PencilSquareIcon, TrashIcon, MapPinIcon, BellAlertIcon, ChevronDownIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    announcements: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
});

const showForm = ref(false);
const editingId = ref(null);
const nudgingId = ref(null);
const expandedReceipts = ref(null);

function toggleReceipts(id) {
    expandedReceipts.value = expandedReceipts.value === id ? null : id;
}

const form = useForm({ title: '', body: '', is_pinned: false });

function startCreate() {
    editingId.value = null;
    form.reset();
    showForm.value = true;
}

function startEdit(announcement) {
    editingId.value = announcement.id;
    form.title = announcement.title;
    form.body = announcement.body;
    form.is_pinned = announcement.is_pinned;
    showForm.value = true;
}

function submit() {
    if (editingId.value) {
        form.patch(route('organizations.announcements.update', [props.organization.id, editingId.value]), {
            preserveScroll: true,
            onSuccess: () => (showForm.value = false),
        });
    } else {
        form.post(route('organizations.announcements.store', props.organization.id), {
            preserveScroll: true,
            onSuccess: () => (showForm.value = false),
        });
    }
}

function destroy(announcement) {
    if (!confirm(`Delete "${announcement.title}"?`)) return;
    router.delete(route('organizations.announcements.destroy', [props.organization.id, announcement.id]), { preserveScroll: true });
}

function nudge(announcement) {
    nudgingId.value = announcement.id;
    router.post(route('organizations.announcements.nudge', [props.organization.id, announcement.id]), {}, {
        preserveScroll: true,
        onFinish: () => (nudgingId.value = null),
    });
}
</script>

<template>
    <Head title="Announcements" />

    <AuthenticatedLayout>
        <template #header>Announcements</template>

        <div class="flex items-center justify-between mb-5">
            <h1 class="font-heading text-xl font-extrabold text-tertiary-900">Announcements</h1>
            <PrimaryButton v-if="canManage && !showForm" type="button" @click="startCreate">
                <PlusIcon class="w-4 h-4 mr-1.5" /> New Announcement
            </PrimaryButton>
        </div>

        <form v-if="showForm" @submit.prevent="submit" class="bg-white border border-neutral-200 rounded-xl p-5 mb-5 space-y-4">
            <div>
                <label for="title" class="block text-sm font-medium text-tertiary-700">Title</label>
                <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" required autofocus />
                <InputError :message="form.errors.title" class="mt-1" />
            </div>
            <div>
                <label for="body" class="block text-sm font-medium text-tertiary-700">Message</label>
                <textarea id="body" v-model="form.body" rows="4" required
                    class="mt-1 block w-full rounded-md border-tertiary-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                <InputError :message="form.errors.body" class="mt-1" />
            </div>
            <label class="flex items-center gap-2 text-sm text-tertiary-600">
                <input type="checkbox" v-model="form.is_pinned" class="rounded border-tertiary-200 text-primary-600 focus:ring-primary-500" />
                Pin to top
            </label>
            <div class="flex items-center gap-3">
                <PrimaryButton :disabled="form.processing">{{ editingId ? 'Save Changes' : 'Post Announcement' }}</PrimaryButton>
                <button type="button" @click="showForm = false" class="rounded-md border border-neutral-200 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-tertiary-600 hover:bg-neutral-100">Cancel</button>
            </div>
        </form>

        <div class="space-y-4">
            <div v-for="a in announcements" :key="a.id" class="bg-white border rounded-xl p-5"
                :class="a.is_pinned ? 'border-secondary-300' : 'border-neutral-200'">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <MapPinIcon v-if="a.is_pinned" class="w-4 h-4 text-secondary-500" />
                        <h3 class="font-heading font-bold text-tertiary-900">{{ a.title }}</h3>
                        <span v-if="!canManage && !a.read_by_me" class="w-2 h-2 rounded-full bg-primary-500" title="Unread"></span>
                    </div>
                    <div v-if="canManage" class="flex items-center gap-3 shrink-0">
                        <button type="button" :disabled="nudgingId === a.id" @click="nudge(a)"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-tertiary-400 hover:text-primary-600 disabled:opacity-50">
                            <BellAlertIcon class="w-3.5 h-3.5" /> Nudge Unread
                        </button>
                        <button type="button" @click="startEdit(a)" class="text-tertiary-300 hover:text-primary-600">
                            <PencilSquareIcon class="w-4 h-4" />
                        </button>
                        <button type="button" @click="destroy(a)" class="text-tertiary-300 hover:text-red-500">
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
                <p class="text-sm text-tertiary-600 mt-2 whitespace-pre-line">{{ a.body }}</p>
                <div class="flex items-center justify-between mt-3">
                    <p class="text-xs text-tertiary-400">{{ a.author }} &middot; {{ a.posted_at }}</p>
                    <button v-if="canManage" type="button" @click="toggleReceipts(a.id)" class="flex items-center gap-1 text-xs font-semibold text-tertiary-400 hover:text-tertiary-800">
                        Read {{ a.read_count }} / {{ a.total_members }}
                        <ChevronDownIcon class="w-3.5 h-3.5 transition-transform" :class="expandedReceipts === a.id ? 'rotate-180' : ''" />
                    </button>
                </div>
                <div v-if="canManage" class="mt-2 h-1 rounded-full bg-neutral-100 overflow-hidden">
                    <div class="h-full bg-primary-500" :style="{ width: `${a.total_members ? (a.read_count / a.total_members) * 100 : 0}%` }"></div>
                </div>

                <div v-if="canManage && expandedReceipts === a.id" class="mt-4 pt-4 border-t border-neutral-100">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-tertiary-400 mb-2">Read Receipts</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1.5">
                        <div v-for="r in a.receipts" :key="r.name" class="flex items-center justify-between gap-2 text-xs">
                            <span class="flex items-center gap-1.5 text-tertiary-600 truncate">
                                <CheckCircleIcon v-if="r.is_read" class="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                                <span v-else class="w-3.5 h-3.5 rounded-full border border-tertiary-200 shrink-0"></span>
                                {{ r.name }}
                            </span>
                            <span class="text-tertiary-400 shrink-0">{{ r.is_read ? r.read_at : 'Unread' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <p v-if="!announcements.length" class="text-center text-sm text-tertiary-400 py-10 bg-white border border-neutral-200 rounded-xl">
                No announcements yet.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
