<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    DocumentArrowUpIcon,
    DocumentIcon,
    DocumentTextIcon,
    PhotoIcon,
    TableCellsIcon,
    PresentationChartBarIcon,
    ArchiveBoxIcon,
    TrashIcon,
    ArrowDownTrayIcon,
    LockClosedIcon,
    SparklesIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';

const props = defineProps({
    organization: { type: Object, required: true },
    files: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
    defaults: { type: Object, required: true },
});

const dragging = ref(false);
const fileInput = ref(null);
const form = useForm({ file: null, is_restricted: props.defaults.restricted, apply_watermark: props.defaults.watermark });

const defaultsForm = useForm({ watermark: props.defaults.watermark, restricted: props.defaults.restricted });

function updateDefault(key, value) {
    defaultsForm[key] = value;
    defaultsForm.post(route('organizations.files.defaults.update', props.organization.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.is_restricted = defaultsForm.restricted;
            form.apply_watermark = defaultsForm.watermark;
        },
    });
}

function iconFor(mimeType) {
    if (!mimeType) return DocumentIcon;
    if (mimeType.startsWith('image/')) return PhotoIcon;
    if (mimeType === 'application/pdf' || mimeType.includes('word')) return DocumentTextIcon;
    if (mimeType.includes('sheet') || mimeType.includes('excel')) return TableCellsIcon;
    if (mimeType.includes('presentation') || mimeType.includes('powerpoint')) return PresentationChartBarIcon;
    if (mimeType.includes('zip')) return ArchiveBoxIcon;
    return DocumentIcon;
}

function upload(file) {
    if (!file) return;
    form.file = file;
    form.post(route('organizations.files.store', props.organization.id), {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            form.reset();
        },
    });
}

function onDrop(event) {
    dragging.value = false;
    upload(event.dataTransfer.files[0]);
}

function onFileChange(event) {
    upload(event.target.files[0]);
    event.target.value = '';
}

function destroy(file) {
    if (!confirm(`Delete "${file.name}"?`)) return;
    router.delete(route('organizations.files.destroy', [props.organization.id, file.id]), { preserveScroll: true });
}

function toggleRestriction(file) {
    router.post(route('organizations.files.toggle-restriction', [props.organization.id, file.id]), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Files" />

    <AuthenticatedLayout>
        <template #header>Files</template>

        <h1 class="font-heading text-xl font-extrabold text-tertiary-900 mb-5">Files</h1>

        <div v-if="canManage" class="bg-white border border-neutral-200 rounded-xl p-5 mb-6">
            <h2 class="flex items-center gap-1.5 font-heading font-bold text-tertiary-900 mb-4">
                <ShieldCheckIcon class="w-4 h-4 text-primary-500" /> Digital Rights Defaults
            </h2>
            <div class="flex items-center justify-between py-1.5">
                <div>
                    <p class="text-sm font-semibold text-tertiary-800">Watermark new images by default</p>
                    <p class="text-xs text-tertiary-400">Applies to newly uploaded JPG/PNG files — doesn't affect files already uploaded.</p>
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
        </div>

        <div v-if="canManage" class="mb-6">
            <div
                @dragover.prevent="dragging = true"
                @dragleave.prevent="dragging = false"
                @drop.prevent="onDrop"
                class="rounded-xl border-2 border-dashed p-8 flex flex-col items-center justify-center text-center transition-colors"
                :class="dragging ? 'border-primary-400 bg-primary-50/40' : 'border-neutral-300 bg-white'"
            >
                <div class="w-11 h-11 rounded-full bg-primary-50 flex items-center justify-center mb-3">
                    <DocumentArrowUpIcon class="w-5 h-5 text-primary-400" />
                </div>
                <p class="text-sm font-semibold text-tertiary-700">Drag and drop a file here</p>
                <p class="text-xs text-tertiary-400 mt-1">PDF, Word, Excel, PowerPoint, images, or zip — up to 10MB</p>

                <div class="flex items-center gap-4 mt-4">
                    <label class="flex items-center gap-1.5 text-xs text-tertiary-600">
                        <input type="checkbox" v-model="form.is_restricted" class="rounded border-tertiary-200 text-primary-600 focus:ring-primary-500" />
                        View-only (no download for members)
                    </label>
                    <label class="flex items-center gap-1.5 text-xs text-tertiary-600">
                        <input type="checkbox" v-model="form.apply_watermark" class="rounded border-tertiary-200 text-primary-600 focus:ring-primary-500" />
                        Watermark (images only)
                    </label>
                </div>

                <button type="button" @click="fileInput.click()" :disabled="form.processing"
                    class="mt-4 inline-flex items-center rounded-md border border-transparent bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-primary-700 disabled:opacity-50">
                    {{ form.processing ? 'Uploading…' : 'Browse Files' }}
                </button>
                <input ref="fileInput" type="file" class="hidden" @change="onFileChange" />
                <p v-if="form.errors.file" class="mt-2 text-xs text-red-600">{{ form.errors.file }}</p>
            </div>
        </div>

        <div class="bg-white border border-neutral-200 rounded-xl divide-y divide-neutral-100">
            <div v-for="file in files" :key="file.id" class="flex items-center justify-between gap-4 px-5 py-3.5">
                <div class="flex items-center gap-3 min-w-0">
                    <component :is="iconFor(file.mime_type)" class="w-8 h-8 text-primary-400 shrink-0" />
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-tertiary-800 truncate flex items-center gap-1.5">
                            {{ file.name }}
                            <LockClosedIcon v-if="file.is_restricted" class="w-3.5 h-3.5 text-secondary-500 shrink-0" title="View-only" />
                            <SparklesIcon v-if="file.is_watermarked" class="w-3.5 h-3.5 text-primary-500 shrink-0" title="Watermarked" />
                        </p>
                        <p class="text-xs text-tertiary-400">{{ file.size }} &middot; {{ file.uploader }} &middot; {{ file.uploaded_at }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <button v-if="canManage" type="button" @click="toggleRestriction(file)"
                        class="text-[10px] font-bold uppercase tracking-wide px-2 py-1 rounded"
                        :class="file.is_restricted ? 'bg-secondary-100 text-secondary-700' : 'bg-neutral-100 text-tertiary-400'">
                        {{ file.is_restricted ? 'View-only' : 'Downloadable' }}
                    </button>
                    <a v-if="file.url" :href="file.url" target="_blank" rel="noopener" class="text-tertiary-400 hover:text-primary-600">
                        <ArrowDownTrayIcon class="w-4 h-4" />
                    </a>
                    <span v-else class="text-xs text-tertiary-300 italic">Restricted</span>
                    <button v-if="canManage" type="button" @click="destroy(file)" class="text-tertiary-300 hover:text-red-500">
                        <TrashIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>
            <p v-if="!files.length" class="px-5 py-10 text-center text-sm text-tertiary-400">No files uploaded yet.</p>
        </div>
    </AuthenticatedLayout>
</template>
