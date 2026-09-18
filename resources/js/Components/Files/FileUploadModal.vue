<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { CloudArrowUpIcon, XMarkIcon, FolderArrowDownIcon, DocumentIcon, FolderPlusIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { iconFor } from '@/utils/fileIcons';
import FilePermissionPicker from './FilePermissionPicker.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    organizationId: { type: String, required: true },
    parentId: { type: String, default: null },
    parentPermissions: { type: Object, default: () => ({ view: null, edit: null }) },
    defaults: { type: Object, required: true },
    permissionKeys: { type: Array, required: true },
});

const emit = defineEmits(['close']);

const form = useForm({
    parent_id: props.parentId,
    files: [],
    relative_paths: [],
    is_restricted: props.defaults.restricted,
    apply_watermark: props.defaults.watermark,
    view_permission: props.parentPermissions.view,
    edit_permission: props.parentPermissions.edit,
});

const folderForm = useForm({
    parent_id: props.parentId,
    name: '',
    view_permission: props.parentPermissions.view,
    edit_permission: props.parentPermissions.edit,
});

const isDraggingOver = ref(false);
const fileInput = ref(null);
const folderInput = ref(null);
const creatingFolder = ref(false);

watch(() => props.show, (show) => {
    if (!show) return;

    form.clearErrors();
    form.parent_id = props.parentId;
    form.files = [];
    form.relative_paths = [];
    form.is_restricted = props.defaults.restricted;
    form.apply_watermark = props.defaults.watermark;
    form.view_permission = props.parentPermissions.view;
    form.edit_permission = props.parentPermissions.edit;
    creatingFolder.value = false;
    folderForm.clearErrors();
    folderForm.name = '';
    folderForm.view_permission = props.parentPermissions.view;
    folderForm.edit_permission = props.parentPermissions.edit;
});

function addFiles(fileList) {
    const files = Array.from(fileList);
    form.files = [...form.files, ...files];
    form.relative_paths = [...form.relative_paths, ...files.map((f) => f.webkitRelativePath || '')];
}

function onFileChange(event) {
    addFiles(event.target.files);
    event.target.value = '';
}

function onDrop(event) {
    isDraggingOver.value = false;
    addFiles(event.dataTransfer.files);
}

function removeFile(index) {
    form.files = form.files.filter((_, i) => i !== index);
    form.relative_paths = form.relative_paths.filter((_, i) => i !== index);
}

function formatSize(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}

function updatePermissions(value) {
    form.view_permission = value.view;
    form.edit_permission = value.edit;
}

function startCreatingFolder() {
    creatingFolder.value = true;
    folderForm.clearErrors();
    folderForm.name = '';
}

function submitFolder() {
    folderForm.parent_id = props.parentId;
    folderForm.view_permission = form.view_permission;
    folderForm.edit_permission = form.edit_permission;
    folderForm.post(route('organizations.files.folders.store', props.organizationId), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}

function submit() {
    form.post(route('organizations.files.store', props.organizationId), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <Modal :show="show" max-width="lg" @close="emit('close')">
        <form @submit.prevent="submit" class="p-6 space-y-5">
            <div>
                <h2 class="font-heading text-lg font-bold text-tertiary-900">Upload</h2>
                <p class="mt-0.5 text-sm text-tertiary-500">Add files, a folder, or create a new folder in this space.</p>
            </div>

            <div
                @dragover.prevent="isDraggingOver = true" @dragleave.prevent="isDraggingOver = false" @drop.prevent="onDrop"
                class="rounded-xl border-2 border-dashed px-4 py-6 flex flex-col items-center justify-center gap-1.5 text-center transition-all duration-200 ease-ios"
                :class="isDraggingOver ? 'border-primary-400 bg-primary-50/60' : 'border-neutral-200 hover:border-primary-300 hover:bg-primary-50/30'">
                <CloudArrowUpIcon class="w-6 h-6 text-primary-400" />
                <p class="text-sm font-semibold text-tertiary-700">Drag files here, or choose below</p>
                <p class="text-xs text-tertiary-400">PDF, Word, Excel, PowerPoint, images, or zip</p>

                <div v-if="!creatingFolder" class="flex items-center gap-2 mt-3">
                    <SecondaryButton type="button" @click="fileInput.click()">
                        <DocumentIcon class="w-4 h-4 mr-1.5" /> Upload File
                    </SecondaryButton>
                    <SecondaryButton type="button" @click="folderInput.click()">
                        <FolderArrowDownIcon class="w-4 h-4 mr-1.5" /> Upload Folder
                    </SecondaryButton>
                    <SecondaryButton type="button" @click="startCreatingFolder">
                        <FolderPlusIcon class="w-4 h-4 mr-1.5" /> Create Folder
                    </SecondaryButton>
                </div>
                <div v-else class="flex items-center gap-2 mt-3 w-full max-w-sm" @click.stop>
                    <TextInput v-model="folderForm.name" type="text" placeholder="Folder name" class="flex-1" autofocus
                        @keydown.enter.prevent="submitFolder" />
                    <SecondaryButton type="button" @click="creatingFolder = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="folderForm.processing || !folderForm.name.trim()" @click="submitFolder">
                        Create
                    </PrimaryButton>
                </div>
                <InputError v-if="creatingFolder" :message="folderForm.errors.name" class="mt-1" />
            </div>
            <input ref="fileInput" type="file" multiple class="hidden" @change="onFileChange"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg,.gif,.zip,.csv,.txt" />
            <input ref="folderInput" type="file" multiple webkitdirectory class="hidden" @change="onFileChange" />
            <InputError :message="form.errors.files" class="-mt-3" />

            <ul v-if="form.files.length" class="space-y-2 max-h-48 overflow-y-auto">
                <li v-for="(file, index) in form.files" :key="index"
                    class="flex items-center gap-2.5 rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2">
                    <component :is="iconFor(file.type)" class="w-4 h-4 text-primary-500 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold text-tertiary-700 truncate">{{ file.webkitRelativePath || file.name }}</p>
                        <p class="text-[11px] text-tertiary-400">{{ formatSize(file.size) }}</p>
                    </div>
                    <button type="button" @click="removeFile(index)" class="text-tertiary-300 hover:text-red-500 transition-colors shrink-0">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </li>
            </ul>

            <div class="border-t border-neutral-100 pt-4">
                <p class="text-xs font-semibold text-tertiary-700 mb-2">Access</p>
                <FilePermissionPicker
                    :model-value="{ view: form.view_permission, edit: form.edit_permission }"
                    @update:model-value="updatePermissions"
                    :permission-keys="permissionKeys"
                />
            </div>

            <div class="border-t border-neutral-100 pt-4 flex items-center gap-4">
                <label class="flex items-center gap-1.5 text-xs text-tertiary-600 cursor-pointer">
                    <input type="checkbox" v-model="form.is_restricted" class="rounded border-tertiary-200 text-primary-600 focus:ring-primary-500" />
                    View-only (no download for members)
                </label>
                <label class="flex items-center gap-1.5 text-xs text-tertiary-600 cursor-pointer">
                    <input type="checkbox" v-model="form.apply_watermark" class="rounded border-tertiary-200 text-primary-600 focus:ring-primary-500" />
                    Watermark (images &amp; PDFs)
                </label>
            </div>

            <div v-if="form.progress" class="h-1.5 rounded-full bg-neutral-100 overflow-hidden">
                <div class="h-full bg-primary-500 transition-all duration-150" :style="{ width: form.progress.percentage + '%' }"></div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <SecondaryButton type="button" @click="emit('close')">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing || !form.files.length">
                    {{ form.processing ? 'Uploading…' : 'Upload' }}
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
