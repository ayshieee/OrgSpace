<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { LinkIcon, PhotoIcon, PaperClipIcon, XMarkIcon, DocumentIcon } from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { vClickOutside } from '@/directives/clickOutside';

const props = defineProps({
    organizationId: { type: String, required: true },
    announcementId: { type: String, required: true },
    parentId: { type: String, default: null },
    autofocus: { type: Boolean, default: false },
});

const emit = defineEmits(['posted', 'cancel']);

const form = useForm({
    body: '',
    parent_id: props.parentId,
    link_url: '',
    images: [],
    files: [],
});

const activePopover = ref(null); // null | 'link'
const linkInput = ref('');
const imageInputEl = ref(null);
const fileInputEl = ref(null);

function togglePopover(name) {
    if (name === 'link' && activePopover.value !== 'link') {
        linkInput.value = form.link_url;
    }
    activePopover.value = activePopover.value === name ? null : name;
}

function applyLink() {
    const url = linkInput.value.trim();

    if (!url) {
        form.link_url = '';
        activePopover.value = null;

        return;
    }

    // Same http(s)-only rule the backend enforces — reject fast client-side.
    if (!/^https?:\/\//i.test(url)) {
        return;
    }

    form.link_url = url;
    activePopover.value = null;
}

function onImageChange(event) {
    form.images = [...form.images, ...Array.from(event.target.files)];
    event.target.value = '';
}

function onFileChange(event) {
    form.files = [...form.files, ...Array.from(event.target.files)];
    event.target.value = '';
}

function removeImage(index) {
    form.images = form.images.filter((_, i) => i !== index);
}

function removeFile(index) {
    form.files = form.files.filter((_, i) => i !== index);
}

function formatSize(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}

const attachmentsError = computed(() => {
    const messages = Object.entries(form.errors)
        .filter(([key]) => key.startsWith('images') || key.startsWith('files'))
        .map(([, message]) => message);

    return messages.length ? messages[0] : null;
});

const hasPendingAttachment = computed(() => form.images.length > 0 || form.files.length > 0 || !!form.link_url);

function submit() {
    if (!form.body.trim()) return;

    form.transform((data) => ({ ...data, parent_id: props.parentId }))
        .post(route('organizations.announcements.comments.store', [props.organizationId, props.announcementId]), {
            forceFormData: true,
            preserveScroll: true,
            only: ['announcements'],
            onSuccess: () => {
                form.reset();
                emit('posted');
            },
        });
}
</script>

<template>
    <div class="mt-2">
        <textarea v-model="form.body" rows="2" :autofocus="autofocus" placeholder="Write a comment..."
            class="w-full text-sm rounded-xl border-neutral-200 focus:border-primary-500 focus:ring-primary-500 resize-none transition-colors duration-150"></textarea>
        <InputError :message="form.errors.body" class="mt-1" />
        <InputError :message="attachmentsError" class="mt-1" />

        <div v-if="hasPendingAttachment" class="mt-2 flex flex-wrap gap-2">
            <div v-for="(file, index) in form.images" :key="'img-' + index"
                class="flex items-center gap-1.5 rounded-lg border border-neutral-200 bg-neutral-50 pl-2 pr-1 py-1 text-xs">
                <PhotoIcon class="w-3.5 h-3.5 text-primary-500 shrink-0" />
                <span class="truncate max-w-[120px]">{{ file.name }}</span>
                <button type="button" @click="removeImage(index)" class="text-tertiary-300 hover:text-red-500 shrink-0">
                    <XMarkIcon class="w-3.5 h-3.5" />
                </button>
            </div>
            <div v-for="(file, index) in form.files" :key="'file-' + index"
                class="flex items-center gap-1.5 rounded-lg border border-neutral-200 bg-neutral-50 pl-2 pr-1 py-1 text-xs">
                <DocumentIcon class="w-3.5 h-3.5 text-primary-500 shrink-0" />
                <span class="truncate max-w-[120px]">{{ file.name }}</span>
                <span class="text-tertiary-400 shrink-0">{{ formatSize(file.size) }}</span>
                <button type="button" @click="removeFile(index)" class="text-tertiary-300 hover:text-red-500 shrink-0">
                    <XMarkIcon class="w-3.5 h-3.5" />
                </button>
            </div>
            <div v-if="form.link_url" class="flex items-center gap-1.5 rounded-lg border border-neutral-200 bg-neutral-50 pl-2 pr-1 py-1 text-xs">
                <LinkIcon class="w-3.5 h-3.5 text-primary-500 shrink-0" />
                <span class="truncate max-w-[160px]">{{ form.link_url }}</span>
                <button type="button" @click="form.link_url = ''" class="text-tertiary-300 hover:text-red-500 shrink-0">
                    <XMarkIcon class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between mt-2">
            <div class="relative flex items-center gap-0.5" v-click-outside="() => { if (activePopover === 'link') activePopover = null; }">
                <button type="button" title="Insert Link" @click="togglePopover('link')"
                    class="w-7 h-7 rounded-md flex items-center justify-center text-tertiary-500 hover:bg-neutral-100 transition-colors duration-150">
                    <LinkIcon class="w-4 h-4" />
                </button>
                <button type="button" title="Upload Image" @click="imageInputEl.click()"
                    class="w-7 h-7 rounded-md flex items-center justify-center text-tertiary-500 hover:bg-neutral-100 transition-colors duration-150">
                    <PhotoIcon class="w-4 h-4" />
                </button>
                <button type="button" title="Upload File" @click="fileInputEl.click()"
                    class="w-7 h-7 rounded-md flex items-center justify-center text-tertiary-500 hover:bg-neutral-100 transition-colors duration-150">
                    <PaperClipIcon class="w-4 h-4" />
                </button>

                <div v-if="activePopover === 'link'"
                    class="absolute top-9 left-0 bg-white border border-neutral-200/60 rounded-xl shadow-elevated p-2.5 flex items-center gap-1.5 z-20 w-64">
                    <input v-model="linkInput" type="url" placeholder="https://example.com" @keydown.enter.prevent="applyLink"
                        class="flex-1 text-xs rounded-md border-neutral-200 focus:border-primary-500 focus:ring-primary-500 py-1.5" />
                    <button type="button" @click="applyLink" class="text-xs font-semibold text-primary-600 hover:text-primary-700 shrink-0">Attach</button>
                </div>

                <input ref="imageInputEl" type="file" multiple accept="image/png,image/jpeg,image/gif" class="hidden" @change="onImageChange" />
                <input ref="fileInputEl" type="file" multiple
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.csv,.txt"
                    class="hidden" @change="onFileChange" />
            </div>

            <div class="flex items-center gap-2">
                <SecondaryButton v-if="parentId" type="button" @click="emit('cancel')" class="!py-1.5 !px-3 !text-xs">Cancel</SecondaryButton>
                <PrimaryButton type="button" :disabled="form.processing || !form.body.trim()" @click="submit" class="!py-1.5 !px-3 !text-xs">
                    Post
                </PrimaryButton>
            </div>
        </div>
    </div>
</template>
