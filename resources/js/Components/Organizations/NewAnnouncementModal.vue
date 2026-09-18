<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Highlight from '@tiptap/extension-highlight';
import Placeholder from '@tiptap/extension-placeholder';
import {
    CloudArrowUpIcon, DocumentIcon, PhotoIcon, XMarkIcon, LinkIcon,
    ListBulletIcon, QueueListIcon, SwatchIcon, PaperClipIcon, FaceSmileIcon, FlagIcon,
} from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import EmojiPicker from './EmojiPicker.vue';
import { vClickOutside } from '@/directives/clickOutside';

const props = defineProps({
    show: { type: Boolean, default: false },
    organizationId: { type: String, required: true },
    editing: { type: Object, default: null }, // null = creating a new announcement
});

const emit = defineEmits(['close']);

const form = useForm({
    title: '',
    body: '',
    is_pinned: false,
    is_important: false,
    link_url: '',
    attachments: [],
});

const fileInput = ref(null);
const isDraggingOver = ref(false);
const activePopover = ref(null); // null | 'highlight' | 'link' | 'emoji'
const linkUrlInput = ref('');

const HIGHLIGHT_COLORS = ['#FFD217', '#A7F3D0', '#BFDBFE', '#FBCFE8', '#FDE68A'];

const editor = useEditor({
    content: '',
    extensions: [
        StarterKit,
        Underline,
        Link.configure({ openOnClick: false, autolink: false }),
        Highlight.configure({ multicolor: true }),
        Placeholder.configure({ placeholder: 'Write your announcement...' }),
    ],
    editorProps: {
        attributes: {
            class: 'announcement-editor-content min-h-[120px] focus:outline-none text-sm text-tertiary-800',
        },
    },
    onUpdate: ({ editor }) => {
        form.body = editor.getHTML();
    },
});

watch(() => props.show, (show) => {
    if (!show) return;

    form.clearErrors();
    activePopover.value = null;

    if (props.editing) {
        form.title = props.editing.title;
        form.body = props.editing.body;
        form.is_pinned = props.editing.is_pinned;
        form.is_important = props.editing.is_important;
    } else {
        form.title = '';
        form.body = '';
        form.is_pinned = false;
        form.is_important = false;
    }

    form.link_url = '';
    form.attachments = [];

    editor.value?.commands.setContent(form.body || '');
});

onBeforeUnmount(() => editor.value?.destroy());

function formatSize(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}

// Laravel returns wildcard-rule errors as "attachments.0", "attachments.1",
// etc — not a bare "attachments" key — so this combines whichever ones
// come back into one message instead of silently showing nothing.
const attachmentsError = computed(() => {
    const messages = Object.entries(form.errors)
        .filter(([key]) => key === 'attachments' || key.startsWith('attachments.'))
        .map(([, message]) => message);

    return messages.length ? messages[0] : null;
});

function addFiles(fileList) {
    const files = Array.from(fileList);
    form.attachments = [...form.attachments, ...files].slice(0, 5);
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
    form.attachments = form.attachments.filter((_, i) => i !== index);
}

function togglePopover(name) {
    if (name === 'link' && activePopover.value !== 'link') {
        linkUrlInput.value = editor.value?.getAttributes('link').href ?? '';
    }
    activePopover.value = activePopover.value === name ? null : name;
}

function applyHighlight(color) {
    editor.value?.chain().focus().toggleHighlight({ color }).run();
    activePopover.value = null;
}

function applyLink() {
    const url = linkUrlInput.value.trim();

    if (!url) {
        editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
        activePopover.value = null;

        return;
    }

    // Same safety rule the backend sanitizer enforces — only http(s), so a
    // typo'd/`javascript:` entry never even makes it into the document.
    if (!/^https?:\/\//i.test(url)) {
        return;
    }

    editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    activePopover.value = null;
}

function insertEmoji(emoji) {
    editor.value?.chain().focus().insertContent(emoji).run();
    activePopover.value = null;
}

function submit() {
    if (props.editing) {
        form.transform((data) => ({ title: data.title, body: data.body, is_pinned: data.is_pinned, is_important: data.is_important }))
            .patch(route('organizations.announcements.update', [props.organizationId, props.editing.id]), {
                preserveScroll: true,
                onSuccess: () => emit('close'),
            });
    } else {
        form.post(route('organizations.announcements.store', props.organizationId), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => emit('close'),
        });
    }
}
</script>

<template>
    <Modal :show="show" max-width="xl" @close="emit('close')">
        <form @submit.prevent="submit" class="p-6 space-y-5">
            <div>
                <h2 class="font-heading text-lg font-bold text-tertiary-900">{{ editing ? 'Edit Announcement' : 'New Announcement' }}</h2>
                <p class="mt-0.5 text-sm text-tertiary-500">Share updates, notices, or resources with your ensemble.</p>
            </div>

            <div>
                <InputLabel for="announcement-title" value="Announcement Title" />
                <TextInput id="announcement-title" v-model="form.title" type="text" class="mt-1 block w-full" required autofocus placeholder="e.g. Spring Gala rehearsal schedule" />
                <InputError :message="form.errors.title" class="mt-1" />
            </div>

            <div>
                <InputLabel value="Message" />
                <div class="mt-1 rounded-xl border border-tertiary-200 overflow-visible focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 transition-colors duration-200">
                    <EditorContent :editor="editor" class="px-3.5 py-3" />

                    <!-- Toolbar -->
                    <div class="flex items-center flex-wrap gap-0.5 border-t border-neutral-100 px-2 py-1.5 relative">
                        <button type="button" title="Bold" @mousedown.prevent="editor?.chain().focus().toggleBold().run()"
                            class="w-7 h-7 rounded-md text-sm font-bold flex items-center justify-center transition-colors duration-150"
                            :class="editor?.isActive('bold') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">B</button>
                        <button type="button" title="Italic" @mousedown.prevent="editor?.chain().focus().toggleItalic().run()"
                            class="w-7 h-7 rounded-md text-sm italic flex items-center justify-center transition-colors duration-150"
                            :class="editor?.isActive('italic') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">I</button>
                        <button type="button" title="Underline" @mousedown.prevent="editor?.chain().focus().toggleUnderline().run()"
                            class="w-7 h-7 rounded-md text-sm underline flex items-center justify-center transition-colors duration-150"
                            :class="editor?.isActive('underline') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">U</button>
                        <button type="button" title="Strikethrough" @mousedown.prevent="editor?.chain().focus().toggleStrike().run()"
                            class="w-7 h-7 rounded-md text-sm line-through flex items-center justify-center transition-colors duration-150"
                            :class="editor?.isActive('strike') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">S</button>

                        <span class="w-px h-5 bg-neutral-200 mx-1"></span>

                        <button type="button" title="Bulleted List" @mousedown.prevent="editor?.chain().focus().toggleBulletList().run()"
                            class="w-7 h-7 rounded-md flex items-center justify-center transition-colors duration-150"
                            :class="editor?.isActive('bulletList') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">
                            <ListBulletIcon class="w-4 h-4" />
                        </button>
                        <button type="button" title="Numbered List" @mousedown.prevent="editor?.chain().focus().toggleOrderedList().run()"
                            class="w-7 h-7 rounded-md flex items-center justify-center transition-colors duration-150"
                            :class="editor?.isActive('orderedList') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">
                            <QueueListIcon class="w-4 h-4" />
                        </button>
                        <button type="button" title="Quote" @mousedown.prevent="editor?.chain().focus().toggleBlockquote().run()"
                            class="w-7 h-7 rounded-md text-base font-serif flex items-center justify-center transition-colors duration-150"
                            :class="editor?.isActive('blockquote') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">&ldquo;</button>

                        <span class="w-px h-5 bg-neutral-200 mx-1"></span>

                        <!-- Highlight -->
                        <div class="relative" v-click-outside="() => { if (activePopover === 'highlight') activePopover = null; }">
                            <button type="button" title="Highlight" @mousedown.prevent="togglePopover('highlight')"
                                class="w-7 h-7 rounded-md flex items-center justify-center transition-colors duration-150"
                                :class="editor?.isActive('highlight') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">
                                <SwatchIcon class="w-4 h-4" />
                            </button>
                            <div v-if="activePopover === 'highlight'" class="absolute bottom-9 left-0 bg-white border border-neutral-200/60 rounded-xl shadow-elevated p-2 flex items-center gap-1.5 z-20">
                                <button v-for="color in HIGHLIGHT_COLORS" :key="color" type="button" @mousedown.prevent="applyHighlight(color)"
                                    class="w-6 h-6 rounded-full border border-black/10 hover:scale-110 transition-transform duration-150" :style="{ backgroundColor: color }"></button>
                                <button type="button" title="Remove highlight" @mousedown.prevent="editor?.chain().focus().unsetHighlight().run(); activePopover = null"
                                    class="w-6 h-6 rounded-full border border-neutral-200 flex items-center justify-center text-tertiary-400 hover:text-red-500">
                                    <XMarkIcon class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Insert link -->
                        <div class="relative" v-click-outside="() => { if (activePopover === 'link') activePopover = null; }">
                            <button type="button" title="Insert Link" @mousedown.prevent="togglePopover('link')"
                                class="w-7 h-7 rounded-md flex items-center justify-center transition-colors duration-150"
                                :class="editor?.isActive('link') ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">
                                <LinkIcon class="w-4 h-4" />
                            </button>
                            <div v-if="activePopover === 'link'" class="absolute bottom-9 left-0 bg-white border border-neutral-200/60 rounded-xl shadow-elevated p-2.5 flex items-center gap-1.5 z-20 w-64">
                                <input v-model="linkUrlInput" type="url" placeholder="https://example.com" @keydown.enter.prevent="applyLink"
                                    class="flex-1 text-xs rounded-md border-neutral-200 focus:border-primary-500 focus:ring-primary-500 py-1.5" />
                                <button type="button" @mousedown.prevent="applyLink" class="text-xs font-semibold text-primary-600 hover:text-primary-700 shrink-0">Apply</button>
                            </div>
                        </div>

                        <!-- Attach files (create-only — the hidden file input below only
                             exists outside edit mode, matching the Attachments section) -->
                        <button v-if="!editing" type="button" title="Attach Files" @mousedown.prevent="fileInput.click()"
                            class="w-7 h-7 rounded-md flex items-center justify-center text-tertiary-500 hover:bg-neutral-100 transition-colors duration-150">
                            <PaperClipIcon class="w-4 h-4" />
                        </button>

                        <!-- Emoji / Stickers -->
                        <div class="relative" v-click-outside="() => { if (activePopover === 'emoji') activePopover = null; }">
                            <button type="button" title="Emoji &amp; Stickers" @mousedown.prevent="togglePopover('emoji')"
                                class="w-7 h-7 rounded-md flex items-center justify-center transition-colors duration-150"
                                :class="activePopover === 'emoji' ? 'bg-primary-100 text-primary-700' : 'text-tertiary-500 hover:bg-neutral-100'">
                                <FaceSmileIcon class="w-4 h-4" />
                            </button>
                            <div v-if="activePopover === 'emoji'" class="absolute bottom-9 left-0 z-20">
                                <EmojiPicker @select="insertEmoji" />
                            </div>
                        </div>

                        <span class="w-px h-5 bg-neutral-200 mx-1"></span>

                        <!-- Important toggle -->
                        <button type="button" title="Mark as Important" @mousedown.prevent="form.is_important = !form.is_important"
                            class="h-7 px-2 rounded-md flex items-center gap-1 text-xs font-semibold transition-colors duration-150"
                            :class="form.is_important ? 'bg-red-50 text-red-600' : 'text-tertiary-500 hover:bg-neutral-100'">
                            <FlagIcon class="w-4 h-4" /> Important
                        </button>
                    </div>
                </div>
                <InputError :message="form.errors.body" class="mt-1" />
            </div>

            <label class="flex items-center gap-2 text-sm text-tertiary-600 cursor-pointer">
                <input type="checkbox" v-model="form.is_pinned" class="rounded border-tertiary-200 text-primary-600 focus:ring-primary-500" />
                Pin to top
            </label>

            <template v-if="!editing">
                <div>
                    <InputLabel for="announcement-link" value="Link (optional)" />
                    <div class="mt-1 relative">
                        <LinkIcon class="w-4 h-4 text-tertiary-300 absolute left-3 top-1/2 -translate-y-1/2" />
                        <TextInput id="announcement-link" v-model="form.link_url" type="url" class="block w-full pl-9" placeholder="https://example.com" />
                    </div>
                    <InputError :message="form.errors.link_url" class="mt-1" />
                </div>

                <div>
                    <InputLabel value="Attachments (optional)" />
                    <button type="button" @click="fileInput.click()"
                        @dragover.prevent="isDraggingOver = true" @dragleave.prevent="isDraggingOver = false" @drop.prevent="onDrop"
                        class="mt-1 w-full rounded-xl border-2 border-dashed px-4 py-6 flex flex-col items-center justify-center gap-1.5 text-center transition-all duration-200 ease-ios"
                        :class="isDraggingOver ? 'border-primary-400 bg-primary-50/60' : 'border-neutral-200 hover:border-primary-300 hover:bg-primary-50/30'">
                        <CloudArrowUpIcon class="w-6 h-6 text-primary-400" />
                        <p class="text-sm font-semibold text-tertiary-700">Drag files here, or click to browse</p>
                        <p class="text-xs text-tertiary-400">Images, PDF, DOC, XLS, PPT, ZIP, CSV, TXT — up to 10 MB each, 5 files max</p>
                    </button>
                    <input ref="fileInput" type="file" multiple class="hidden" @change="onFileChange"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg,.gif,.zip,.csv,.txt" />
                    <InputError :message="attachmentsError" class="mt-1" />

                    <ul v-if="form.attachments.length" class="mt-3 space-y-2">
                        <li v-for="(file, index) in form.attachments" :key="index"
                            class="flex items-center gap-2.5 rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2">
                            <PhotoIcon v-if="file.type.startsWith('image/')" class="w-4 h-4 text-primary-500 shrink-0" />
                            <DocumentIcon v-else class="w-4 h-4 text-primary-500 shrink-0" />
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-tertiary-700 truncate">{{ file.name }}</p>
                                <p class="text-[11px] text-tertiary-400">{{ formatSize(file.size) }}</p>
                            </div>
                            <button type="button" @click="removeFile(index)" class="text-tertiary-300 hover:text-red-500 transition-colors shrink-0">
                                <XMarkIcon class="w-4 h-4" />
                            </button>
                        </li>
                    </ul>
                </div>
            </template>

            <div class="flex justify-end gap-3 pt-2">
                <SecondaryButton type="button" @click="emit('close')">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing">{{ editing ? 'Save Changes' : 'Publish Announcement' }}</PrimaryButton>
            </div>
        </form>
    </Modal>
</template>

<style>
.announcement-editor-content ul { list-style: disc; padding-left: 1.5rem; }
.announcement-editor-content ol { list-style: decimal; padding-left: 1.5rem; }
.announcement-editor-content blockquote {
    border-left: 3px solid #2E418D;
    padding-left: 0.75rem;
    margin-left: 0;
    color: #4B5563;
    font-style: italic;
}
.announcement-editor-content mark { border-radius: 0.2rem; padding: 0 0.15rem; }
.announcement-editor-content a { color: #2E418D; text-decoration: underline; }
.announcement-editor-content p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #9CA3AF;
    pointer-events: none;
    height: 0;
}
</style>
