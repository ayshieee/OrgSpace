<script setup>
import { ref } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { PlusIcon, ShieldCheckIcon, LinkIcon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import FileBreadcrumbs from '@/Components/Files/FileBreadcrumbs.vue';
import FileGrid from '@/Components/Files/FileGrid.vue';
import FileUploadModal from '@/Components/Files/FileUploadModal.vue';
import FilePreviewModal from '@/Components/Files/FilePreviewModal.vue';
import RenameFileModal from '@/Components/Files/RenameFileModal.vue';
import ManageFilePermissionsModal from '@/Components/Files/ManageFilePermissionsModal.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    folder: { type: Object, default: null },
    breadcrumbs: { type: Array, default: () => [] },
    items: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
    currentFolderTier: { type: String, default: null },
    permissionKeys: { type: Array, default: () => [] },
    microsoftConnected: { type: Boolean, default: false },
    microsoftAccountEmail: { type: String, default: null },
    defaults: { type: Object, required: true },
});

const canUpload = props.currentFolderTier === 'edit' || props.currentFolderTier === 'manage';

const defaultsForm = useForm({ watermark: props.defaults.watermark, restricted: props.defaults.restricted });

function updateDefault(key, value) {
    defaultsForm[key] = value;
    defaultsForm.post(route('organizations.files.defaults.update', props.organization.id), { preserveScroll: true });
}

function navigate(folderId) {
    router.get(route('organizations.files.index', props.organization.id), folderId ? { folder: folderId } : {}, { preserveState: true });
}

const showUpload = ref(false);
const previewItem = ref(null);
const renameItem = ref(null);
const showRename = ref(false);
const permissionsItem = ref(null);
const showPermissions = ref(false);

function openItem(item) {
    if (item.is_folder) {
        navigate(item.id);
    } else {
        previewItem.value = item;
    }
}

function startRename(item) {
    renameItem.value = item;
    showRename.value = true;
}

function startManagePermissions(item) {
    permissionsItem.value = item;
    showPermissions.value = true;
}

function destroyItem(item) {
    if (!confirm(`Delete "${item.name}"?`)) return;
    router.delete(route('organizations.files.destroy', [props.organization.id, item.id]), { preserveScroll: true });
}

function toggleRestriction(item) {
    router.post(route('organizations.files.toggle-restriction', [props.organization.id, item.id]), {}, { preserveScroll: true });
}

async function openOffice(item) {
    previewItem.value = item;
}

function disconnectMicrosoft() {
    if (!confirm('Disconnect Microsoft? Files already opened in OneDrive remain there.')) return;
    router.post(route('organizations.microsoft.disconnect', props.organization.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Files" />

    <AuthenticatedLayout>
        <template #header>Files</template>

        <div class="flex items-center justify-between mb-5">
            <h1 class="font-heading text-xl font-extrabold text-tertiary-900">Files</h1>
            <PrimaryButton v-if="canUpload" type="button" @click="showUpload = true">
                <PlusIcon class="w-4 h-4 mr-1.5" /> Upload
            </PrimaryButton>
        </div>

        <div v-if="canManage" class="bg-white border border-neutral-200/60 rounded-2xl shadow-soft hover:shadow-elevated transition-shadow duration-300 p-5 mb-6">
            <h2 class="flex items-center gap-1.5 font-heading font-bold text-tertiary-900 mb-4">
                <ShieldCheckIcon class="w-4 h-4 text-primary-500" /> Digital Rights Defaults
            </h2>
            <div class="flex items-center justify-between py-1.5">
                <div>
                    <p class="text-sm font-semibold text-tertiary-800">Watermark new images by default</p>
                    <p class="text-xs text-tertiary-400">Applies to newly uploaded JPG/PNG/PDF files — doesn't affect files already uploaded.</p>
                </div>
                <ToggleSwitch :model-value="defaultsForm.watermark" @update:model-value="(v) => updateDefault('watermark', v)" />
            </div>
            <div class="flex items-center justify-between py-1.5 mt-2 pt-2 border-t border-neutral-100">
                <div>
                    <p class="text-sm font-semibold text-tertiary-800">Restrict new uploads by default</p>
                    <p class="text-xs text-tertiary-400">New files are view-only for members unless you turn this off per upload.</p>
                </div>
                <ToggleSwitch :model-value="defaultsForm.restricted" @update:model-value="(v) => updateDefault('restricted', v)" />
            </div>
            <div class="flex items-center justify-between py-1.5 mt-2 pt-2 border-t border-neutral-100">
                <div>
                    <p class="text-sm font-semibold text-tertiary-800 flex items-center gap-1.5">
                        <LinkIcon class="w-3.5 h-3.5 text-tertiary-400" /> Microsoft Office integration
                    </p>
                    <p class="text-xs text-tertiary-400">
                        <template v-if="microsoftConnected">Connected as {{ microsoftAccountEmail }}. Members can open Word/Excel/PowerPoint files for editing in the browser.</template>
                        <template v-else>Not connected. Connect a Microsoft account to enable browser-based editing for Office documents.</template>
                    </p>
                </div>
                <SecondaryButton v-if="microsoftConnected" type="button" @click="disconnectMicrosoft">Disconnect</SecondaryButton>
                <Link v-else :href="route('organizations.microsoft.connect', organization.id)">
                    <SecondaryButton type="button">Connect</SecondaryButton>
                </Link>
            </div>
        </div>

        <FileBreadcrumbs :breadcrumbs="breadcrumbs" @navigate="navigate" />

        <FileGrid :items="items"
            @open="openItem" @rename="startRename" @delete="destroyItem" @toggle-restriction="toggleRestriction"
            @open-office="openOffice" @manage-permissions="startManagePermissions" />

        <FileUploadModal v-if="canUpload" :show="showUpload" :organization-id="organization.id"
            :parent-id="folder?.id ?? null"
            :parent-permissions="{ view: folder?.view_permission ?? null, edit: folder?.edit_permission ?? null }"
            :defaults="defaults" :permission-keys="permissionKeys" @close="showUpload = false" />

        <FilePreviewModal v-if="previewItem" :item="previewItem" :organization-id="organization.id"
            :microsoft-connected="microsoftConnected" :can-manage="canManage" @close="previewItem = null" />

        <RenameFileModal :show="showRename" :organization-id="organization.id" :item="renameItem" @close="showRename = false" />

        <ManageFilePermissionsModal :show="showPermissions" :organization-id="organization.id"
            :item="permissionsItem" :permission-keys="permissionKeys" @close="showPermissions = false" />
    </AuthenticatedLayout>
</template>
