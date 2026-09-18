<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    XMarkIcon, ArrowDownTrayIcon, ArrowTopRightOnSquareIcon,
    ExclamationTriangleIcon, CloudArrowUpIcon,
} from '@heroicons/vue/24/outline';
import { iconFor } from '@/utils/fileIcons';

const OFFICE_MIMES = [
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
];

const props = defineProps({
    item: { type: Object, required: true },
    organizationId: { type: String, required: true },
    microsoftConnected: { type: Boolean, default: false },
    canManage: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const isImage = computed(() => (props.item.mime_type || '').startsWith('image/'));
const isPdf = computed(() => props.item.mime_type === 'application/pdf');
const isConverted = computed(() => props.item.preview_status === 'converted');
const isFailed = computed(() => props.item.preview_status === 'failed');
const icon = computed(() => iconFor(props.item.mime_type));

const canEdit = computed(() => props.item.permission_tier === 'edit' || props.item.permission_tier === 'manage');
const isOfficeDoc = computed(() => OFFICE_MIMES.includes(props.item.mime_type));

const openingOffice = ref(false);
const pulling = ref(false);
const officeError = ref('');

async function openInOffice() {
    officeError.value = '';
    openingOffice.value = true;

    try {
        const { data } = await window.axios.post(
            route('organizations.files.office.open', [props.organizationId, props.item.id])
        );
        window.open(data.edit_url, '_blank', 'noopener,noreferrer');
        props.item.ms_connected_file = true;
    } catch (e) {
        officeError.value = e.response?.data?.message || 'Could not open this document in Office. Please try again.';
    } finally {
        openingOffice.value = false;
    }
}

function pullUpdatedVersion() {
    pulling.value = true;
    router.post(route('organizations.files.office.pull', [props.organizationId, props.item.id]), {}, {
        preserveScroll: true,
        onFinish: () => { pulling.value = false; },
    });
}
</script>

<template>
    <div class="fixed inset-0 z-50 bg-tertiary-900/60 flex items-center justify-center p-4" @click.self="emit('close')">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-neutral-200 shrink-0">
                <p class="font-heading font-bold text-tertiary-900 truncate">{{ item.name }}</p>
                <button type="button" @click="emit('close')" class="text-tertiary-400 hover:text-tertiary-700 shrink-0 ml-3">
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>

            <div class="flex-1 overflow-auto bg-neutral-50 p-5 flex items-center justify-center">
                <img v-if="isImage" :src="item.preview_url" class="max-w-full max-h-[75vh] object-contain" :alt="item.name" />
                <iframe v-else-if="isPdf || isConverted" :src="item.preview_url" class="w-full h-[75vh] rounded border border-neutral-200" title="File preview" />
                <div v-else-if="isFailed" class="text-center text-tertiary-400 text-sm py-10 max-w-sm">
                    <ExclamationTriangleIcon class="w-10 h-10 mx-auto mb-3 text-amber-400" />
                    <p class="text-tertiary-600 font-medium">A preview couldn't be generated for this file.</p>
                    <p class="text-xs mt-1">Open it in a new tab or download it to view the original.</p>
                </div>
                <div v-else class="text-center text-tertiary-400 text-sm py-10">
                    <component :is="icon" class="w-10 h-10 mx-auto mb-3 text-tertiary-300" />
                    <p>Preview isn't available for this file type.</p>
                    <p class="text-xs mt-1">Open it in a new tab or download it instead.</p>
                </div>
            </div>

            <div v-if="isOfficeDoc && canEdit" class="px-5 py-3 border-t border-neutral-200 shrink-0 bg-primary-50/40">
                <div v-if="!microsoftConnected" class="text-xs text-tertiary-500">
                    <template v-if="canManage">Connect Microsoft above on the Files page to edit this document in Word, Excel, or PowerPoint for the web.</template>
                    <template v-else>Ask an organization manager to connect Microsoft to enable editing this document in the browser.</template>
                </div>
                <div v-else class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="openInOffice" :disabled="openingOffice"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-transparent bg-primary-500 px-3 py-2 text-xs font-semibold text-white shadow-soft hover:bg-primary-600 transition-colors disabled:opacity-60">
                        <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                        {{ openingOffice ? 'Preparing document…' : (item.ms_connected_file ? 'Open in Office Again' : 'Open in Office') }}
                    </button>
                    <button v-if="item.ms_connected_file" type="button" @click="pullUpdatedVersion" :disabled="pulling"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 px-3 py-2 text-xs font-semibold text-tertiary-700 hover:bg-neutral-50 transition-colors disabled:opacity-60">
                        <CloudArrowUpIcon class="w-4 h-4" /> {{ pulling ? 'Saving…' : 'Save Updated Version to OrgSpace' }}
                    </button>
                    <p class="w-full text-[11px] text-tertiary-400">
                        Editing opens a OneDrive copy in your browser. Changes there are not saved back to OrgSpace automatically — use "Save Updated Version" after you're done editing.
                    </p>
                    <p v-if="officeError" class="w-full text-[11px] text-red-500">{{ officeError }}</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-neutral-200 shrink-0">
                <a :href="item.preview_url" target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 px-3 py-2 text-xs font-semibold text-tertiary-700 hover:bg-neutral-50 transition-colors">
                    <ArrowTopRightOnSquareIcon class="w-4 h-4" /> Open in New Tab
                </a>
                <a v-if="item.download_url" :href="item.download_url"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-transparent bg-gradient-to-b from-secondary-400 to-secondary-500 px-3 py-2 text-xs font-semibold text-tertiary-900 shadow-soft hover:shadow-elevated hover:from-secondary-300 hover:to-secondary-400 transition-all duration-200 ease-ios">
                    <ArrowDownTrayIcon class="w-4 h-4" /> Download
                </a>
            </div>
        </div>
    </div>
</template>
